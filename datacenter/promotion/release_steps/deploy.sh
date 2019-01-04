#!/bin/bash

SCRIPT=`basename $0`
DIR=`dirname $0`
#TSTAMP=`date "+%H:%M:%S"`
#DSTAMP=`date +'%Y-%m-%d'`
MAINTDONE=`date "+%l:%M %p on %b %e, %Y" --date='5 minutes'`

export HOSTPREFIX="${HOSTNAME:0:4}"
HOSTSUFFIX="${HOSTNAME:4}"
VERSIONHOST="auth.XXXXXX.com"

DBPASS=`cat ~/dbpass.txt`
BACKUPDIR=/var/db_backups
export SCHEMALIST="access auth dashboard xxxxxx_internal ebs emissions equipment fiberaccess leasing shifttech tour"

source "$(dirname $0)/deploy_functions.sh"

if [ $HOSTSUFFIX != "DB1" ]
then
    echo "This must be run on the database server."
    exit 9
fi
if [ $HOSTPREFIX == "LXX" ]
then
    export TARGETDIR="test"
    BRANCH="release"
    VERSIONHOST="test-auth.XXXXXX.com"
elif [ $HOSTPREFIX == "PXX" ]
then 
    export TARGETDIR="prod"
    BRANCH="master"
else
    export TARGETDIR="dev"
    REPLY=''
    while [ "$REPLY" != "n" ] && [ "$REPLY" != "y" ]
        do read -p "On development, the source code deployment will be skipped.
Do you wish to sync the development database from prod and run database deployment (y/n)? "
    done
    if [ "$REPLY" == "y" ]
    then
        echo "backing up and compressing current database to snapshot: ${BACKUPDIR}/${LASTSNAPSHOTGZ}"
        mkdir -p ${BACKUPDIR}
        LASTSNAPSHOT="${TARGETDIR}db_`date "+%Y%m%d_%H%M%S"`.sql"
        LASTSNAPSHOTGZ="${LASTSNAPSHOT}.gz"
        mysqldump -q -c --skip-set-charset -u${USER} -p${DBPASS} -R --databases ${SCHEMALIST} > ${BACKUPDIR}/${LASTSNAPSHOT}
        gzip -f ${BACKUPDIR}/${LASTSNAPSHOT}
        executeDeploymentScripts ${DIR}/preCopy
        executeDeploymentScripts ${DIR}/postCopy
    fi
    exit 0
fi

PRIORTAG=`curl -q https://${VERSIONHOST}/build_number.txt`
LASTSNAPSHOT="${TARGETDIR}_tag${PRIORTAG}.sql"
LASTSNAPSHOTGZ="${LASTSNAPSHOT}.gz"
echo "entering Apache maintenance mode"
enterMaintenance

echo "backing up and compressing current database to snapshot: ${BACKUPDIR}/${LASTSNAPSHOTGZ}"
mkdir -p ${BACKUPDIR}
mysqldump -q -c --skip-set-charset -u${USER} -p${DBPASS} -R --all-databases > ${BACKUPDIR}/${LASTSNAPSHOT}
gzip -f ${BACKUPDIR}/${LASTSNAPSHOT}

GITHOST="GXXXWEB2"
GITREPONAME="dev"
export GITREPODIR="${HOME}/tmp/${GITREPONAME}"



# check if tmp repo exists
if [ ! -d ${GITREPODIR} ]
then
    mkdir -p ${HOME}/tmp
    cd ${HOME}/tmp
    git clone ssh://git@${GITHOST}:/opt/git/${GITREPONAME}.git
fi

cd ${GITREPODIR}
git checkout -f $BRANCH
git pull
TAG=''
echo "calling getLatestTag"
getLatestTag TAG
checkFail $? 1 "No viable tag found"
echo "called getLatestTag"
echo "Deploying tag ${TAG}"
git checkout -f ${TAG}
checkFail $? 1 "unable to checkout ${TAG}"
echo -n "${TAG}" > auth/src/web/build_number.txt
echo -n "${TAG}" > datapoint/src/web/build_number.txt
echo -n "${TAG}" > portal/src/web/build_number.txt
echo -n "${TAG}" > inside/src/web/build_number.txt

## shut down remote apache instances or put into maintenance mode

### run pre-copy .sh/.sql files
executeDeploymentScripts ${GITREPODIR}/promotion/release_steps/preCopy

rm -f ${GITREPODIR}/crons/active
CRONDIRECTIVES=""
if [ -e ${GITREPODIR}/crons/${HOSTPREFIX}WEB1 ]
then
    echo "Creating crons/active symlink ${GITREPODIR}/crons/active -> ${GITREPODIR}/crons/${HOSTPREFIX}WEB1"
    ln -s ${HOSTPREFIX}WEB1 ${GITREPODIR}/crons/active
    CRONDIRECTIVES="--include='crons/${HOSTPREFIX}WEB1' --include='crons/active'"
fi

### copy files to target systems
echo "copying to ${HOSTPREFIX}WEB1"
rsync -h --stats -azOJ --delete-delay --chmod=Fg+w ${GITREPODIR}/ --exclude='.git' --exclude='promotion' --include='global' --include='defaultAPI' --include='portal' --include='auth' ${CRONDIRECTIVES} --exclude='datapoint' --exclude='inside' --exclude='crons/output/*.log' --exclude='crons/output/*.csv' ${HOSTPREFIX}WEB1:/var/www/${TARGETDIR}/

rm -f ${GITREPODIR}/crons/active
CRONDIRECTIVES=""
if [ -e ${GITREPODIR}/crons/${HOSTPREFIX}WEB2 ]
then
    echo "Creating crons/active symlink ${GITREPODIR}/crons/active -> ${GITREPODIR}/crons/${HOSTPREFIX}WEB2"
    ln -s ${HOSTPREFIX}WEB2 ${GITREPODIR}/crons/active
    CRONDIRECTIVES="--include='crons/${HOSTPREFIX}WEB2' --include='crons/active'"
fi

echo "copying to ${HOSTPREFIX}WEB2"
chmod 777 datapoint/src/web/comm_losses
rsync -h --stats -azOJ --delete-delay --chmod=Fg+w ${GITREPODIR}/ --exclude='.git' --exclude='promotion' --include='global' --exclude='defaultAPI' --exclude='portal' --exclude='auth' --include="crons/${HOSTPREFIX}WEB2" --include='crons/active' --include='datapoint' --include='inside' --exclude='crons/output/*.log' --exclude='crons/output/*.csv' ${HOSTPREFIX}WEB2:/var/www/${TARGETDIR}/
rm -f ${GITREPODIR}/crons/active

### run post-copy .sh/.sql files
executeDeploymentScripts ${GITREPODIR}/promotion/release_steps/postCopy

## start remote apache instances or disable maintenance mode
exit 0
