#!/bin/bash
# {{{ function quit
#
quit() {
    echo "Error: $1"
    exit 1
}
export -f quit
# }}}

# {{{ test, define args & env variables
[[ -z "$1" ]] && quit "give me env as first arg"
env_file=".env.$1.local"
[[ ! -r "$env_file" ]] && quit "$env_file: file not found or not readable (bad env given as first arg)"
[[ -z "$2" ]] && quit "give me json payload file as second arg"
payload="$2"
[[ ! -r "$payload" ]] && quit "$payload:  file not found or not readable (bad payload file given as second arg)"
source $env_file
[[ -z "$ALMA_API_URL" ]] && quit "ALMA_API_URL not set in your $env_file"
[[ -z "$ALMA_API_KEY" ]] && quit "ALMA_API_KEY not set in your $env_file"
# }}}

curl $ALMA_API_URL/v1/payments \
    -H "Authorization: Alma-Auth $ALMA_API_KEY" \
    -H "Content-type: application/json" \
    -X POST --data-binary "@$payload" 
