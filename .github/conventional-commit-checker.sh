#!/bin/bash
IFS=$'\n'
for log in $(git log origin/main..$(git branch --show-current) --format='%s'); do
    echo $log | grep -P '^(?P<type>[a-z]+)(\((?P<scope>[a-z-]+)\))?(?P<breaking>!)?: (\[(?P<ticket>[A-Z0-9-]+)\] )?(?P<message>[^\n]+)(\n\n(?P<infos>.+))?$' >/dev/null
	if [ $? -eq 1 ]; then
      echo -e "The following log don't follow Conventionnal commits:\n\t$log"
      exit 1
    fi
done
unset IFS
