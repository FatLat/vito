sudo journalctl _COMM=sudo -n 300 --no-pager -o short-iso 2>/dev/null | grep 'COMMAND=' | sed 's/^/sudo\t/' || true
for file in /root/.bash_history /home/*/.bash_history; do
    if sudo test -f "$file"; then
        owner=$(basename "$(dirname "$file")")
        sudo tail -n 100 "$file" | sed "s/^/bash\t$owner\t/"
    fi
done
