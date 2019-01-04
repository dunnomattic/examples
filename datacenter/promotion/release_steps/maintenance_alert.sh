TARGETVAL=0
if [ $# -eq 1 ]
then
    re='^[0-9]+$'
    if ! [[ $1 =~ $re ]] || [ "$1" -gt 24 ]
    then
            echo "WARNING: invalid range, assuming 0 (turn message off)"
    else
        TARGETVAL="${1}"
    fi
else
    echo 
    echo "Usage:"
    echo
    echo "  Display a 15-min window starting on the parameter hour between 1AM and 12AM(midnight):"
    echo "  $0 [1-24]	Sets maintenance variable on auth.XXXXXX.com to a specific HH24"
    echo
    echo "  Hide maintenance message:"
    echo "  $0 0		Sets maintenance announcement variable on auth.XXXXXX.com to 0"
    echo
    echo
    exit 1
fi
echo "Set maintenanceTrigger Apache variable to: ${TARGETVAL}"
echo "copying file to PRPPWEB1 @ /var/www/prod/auth/src/web/.htaccess ..."
echo "SetEnv maintenanceTrigger ${TARGETVAL}" > .htaccess 
chmod g+w .htaccess
rsync -h --stats -a --chmod=g+w .htaccess PRPPWEB1:/var/www/prod/auth/src/web/
rsync -h --stats -a --chmod=g+w .htaccess PRPPWEB1:/var/www/prod/portal/src/web/
rsync -h --stats -a --chmod=g+w .htaccess PRPPWEB2:/var/www/prod/inside/src/web/
rm .htaccess
