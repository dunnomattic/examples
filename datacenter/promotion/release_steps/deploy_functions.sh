#!/bin/bash

# getLatestTag $TAG
function getLatestTag() {
    local _retvariable=$1
    local _retval=`git describe --abbrev=0 --tags 2>/dev/null` 
    local _retcode=0
    if [ -z $_retval ]
    then
        _retcode=2
    fi
    eval $_retvariable=\$_retval
    echo "return $_retcode, val $_retval"
    return $_retcode
}

# checkFail $retCode $fatal $failMessage
function checkFail() {
    if [ $1 -ne 0 ]
    then
        echo -n "Operation failed"
        echo
        if  [ $2 -eq 1 ]
        then
            if [ -n "$3" ]
            then
                echo $3
            fi
            echo "...aborting."
            exit $1
        fi
    fi
}

# checkFail $retCode $fatal $failMessage
function checkFailRollback() {
    if [ $1 -ne 0 ]
    then
        echo -n "Operation failed"
        echo
        if  [ $2 -eq 1 ]
        then
            if [ -n "$3" ]
            then
                echo $3
            fi
            echo "...aborting."
            if [ ${TARGETDIR} == "prod" ]
            then
                rollbackRelease ${PRIORTAG}
            fi
            exit $1
        fi
    fi
}

function rollbackRelease() {
    echo "Rolling Back to ${PRIORTAG} using ${LASTSNAPSHOTGZ}"
    if [ -f ${BACKUPDIR}/${LASTSNAPSHOTGZ} ]
    then
        gunzip ${BACKUPDIR}/${LASTSNAPSHOTGZ}
        mysql -u${USER} -p${DBPASS} < ${BACKUPDIR}/${LASTSNAPSHOT}
        git checkout -f ${PRIORTAG}
        echo -n "${PRIORTAG}" > auth/src/web/build_number.txt
        echo -n "${PRIORTAG}" > datapoint/src/web/build_number.txt
        echo -n "${PRIORTAG}" > portal/src/web/build_number.txt
        echo -n "${PRIORTAG}" > inside/src/web/build_number.txt
        echo "copying to ${HOSTPREFIX}WEB1"
        rsync -h --stats -azOJ --delete-delay --chmod=Fg+w ${GITREPODIR}/ --exclude='.git' --exclude='promotion' --include='global' --include='defaultAPI' --include='portal' --include='auth' --exclude='datapoint' --exclude='inside' ${HOSTPREFIX}WEB1:/var/www/${TARGETDIR}/
        echo "copying to ${HOSTPREFIX}WEB2"
        rsync -h --stats -azOJ --delete-delay --chmod=Fg+w ${GITREPODIR}/ --exclude='.git' --exclude='promotion' --include='global' --exclude='defaultAPI' --exclude='portal' --exclude='auth' --include='datapoint' --include='inside' ${HOSTPREFIX}WEB2:/var/www/${TARGETDIR}/
        gzip -f ${BACKUPDIR}/${LASTSNAPSHOT}
    fi
}

function executeDeploymentScripts() {
    PRECOPYDIR=$1
    if [ ! -d $PRECOPYDIR ]
    then
        return 0
    fi

    SORTEDFILES=(`ls ${PRECOPYDIR}`)

    if [ ${#SORTEDFILES[@]} == 0 ]; then
        echo "0 import files found.  Aborting."
        exit 9
    fi

    for filename in ${SORTEDFILES[@]}
    do
        #echo ${PRECOPYDIR}/$filename
        if [ -e ${PRECOPYDIR}/$filename ]
        then
            TYPE="${filename#*[.]}"
            echo "found .${TYPE} file $filename"
            case $TYPE in 
                "sh") 
                    echo "execute shell command ${PRECOPYDIR}/$filename"
                    ${PRECOPYDIR}/$filename
                    checkFailRollback $? 1 "Failed to execute ${PRECOPYDIR}/$filename"
                ;;
                "sql")
                    echo "execute sql script ${PRECOPYDIR}/$filename"
                    mysql -u${USER} -p${DBPASS} < ${PRECOPYDIR}/$filename
                    checkFailRollback $? 1 "Failed run SQL ${PRECOPYDIR}/$filename"
                ;;
            esac
        fi
    done
}

function enterMaintenance() {
    HTACCESSDATA="<IfModule mod_rewrite.c>
    RewriteEngine on
    RewriteCond %{REQUEST_URI} !^\\/build_number.txt [NC]
    RewriteRule .* - [R=503,L]
</IfModule>
ErrorDocument 503 \"<html><head><meta http-equiv='refresh' content='30'></head><body style='margin: 0px 0px 0px 0px'><div style='padding: 8px 8px 8px 8px; background-color: #eeeeee; font-color: #FFFFFF;'><img src='https://portal.XXXXXX.com/img/logo.png?v=2' border='0' style='margin: 0px 0px 0px 0px; padding: 0px 0px 0px 0px; height: 80px;'></div><h2>Maintenance</h2><br>We apologize for the inconvenience, but the site is temporarily unavailable for upgrades and maintenance.  This shouldn't take long.  We expect to be complete by ${MAINTDONE}.<br><br>If you have any questions, let us know at <a href='mailto:softwaredevelopment@XXXXXY.com?subject=Maintenance'>SoftwareDevelopment@XXXXXY.com</a>.</body></html>\""
    echo "${HTACCESSDATA}" > ${HOME}/tmp/maintenance.htaccess
    chgrp XXXdev ${HOME}/tmp/maintenance.htaccess
    chmod a+w ${HOME}/tmp/maintenance.htaccess

    scp -p ${HOME}/tmp/maintenance.htaccess ${HOSTPREFIX}WEB1:/var/www/${TARGETDIR}/auth/src/web/.htaccess
    scp -p ${HOME}/tmp/maintenance.htaccess ${HOSTPREFIX}WEB1:/var/www/${TARGETDIR}/portal/src/web/.htaccess
    scp -p ${HOME}/tmp/maintenance.htaccess ${HOSTPREFIX}WEB2:/var/www/${TARGETDIR}/inside/src/web/.htaccess
    rm ${HOME}/tmp/maintenance.htaccess
}

