#!/bin/bash
# Copy or create file acording to git

# create directory
function create_dir() {
    local IFS="/"
    shift
    echo "creating directory $*"
    mkdir -p "$*"
    touch /www/wwwroot/test.billtime.in/"$*"/${i}
}

for i in $(cat tmp.txt)
do
    # check if file exits. if it exits just copy file from git repo. if does not exits create one.
    if [ -e ./${i} ]
    then
        cp -r ./${i} /www/wwwroot/test.billtime.in/${i}; echo "copying file from ${i} to /www/wwwroot/test.billtime.in/${i}"
    else
        # splitin string to newarr array
        IFS="/"
        read -ra newarr <<< "${1}"

        # creating an array
        declare -a NEWDIR=()
        # looping over an array
        for ((i = 0; i < ${#newarr[@]}; i++)); do
            echo "${newarr[i]} $i"
        done
        create_dir "${NEWDIR[@]}"
        echo "creating file ${i}"
    fi
done

rm tmp.txt

