#!/bin/bash
echo "Installing crons"

if [ -e ${GITREPODIR}/crons/${HOSTPREFIX}WEB1/websvc.crontab ]
then
    echo "${GITREPODIR}/crons/${HOSTPREFIX}WEB1/websvc.crontab found, executing sudo crontab on ${HOSTPREFIX}WEB1"
    ssh ${HOSTPREFIX}WEB1 "sudo crontab -u websvc /var/www/${TARGETDIR}/crons/active/websvc.crontab"
fi

if [ -e ${GITREPODIR}/crons/${HOSTPREFIX}WEB2/websvc.crontab ]
then
    echo "${GITREPODIR}/crons/${HOSTPREFIX}WEB2/websvc.crontab found, executing sudo crontab on ${HOSTPREFIX}WEB2"
    ssh ${HOSTPREFIX}WEB2 "sudo crontab -u websvc /var/www/${TARGETDIR}/crons/active/websvc.crontab"
fi

