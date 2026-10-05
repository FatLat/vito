. /etc/os-release 2>/dev/null || true
printf 'info\thostname\t%s\n' "$(hostname)"
printf 'info\tos\t%s\n' "${PRETTY_NAME:-}"
printf 'info\tkernel\t%s\n' "$(uname -r)"
printf 'info\tarchitecture\t%s\n' "$(uname -m)"
printf 'info\tcpu\t%s\n' "$(lscpu 2>/dev/null | awk -F: '/Model name/ {sub(/^ +/, "", $2); print $2; exit}')"
printf 'info\ttimezone\t%s\n' "$(cat /etc/timezone 2>/dev/null || timedatectl show -p Timezone --value 2>/dev/null || true)"
df -PTB1 -x tmpfs -x devtmpfs -x squashfs -x overlay -x efivarfs 2>/dev/null | awk 'NR>1 {printf "disk\t%s\t%s\t%s\t%s\t%s\n", $7, $1, $3, $4, $5}' || true
sudo timeout 20 du -xsb /home/* /var/* /opt /root /tmp /usr 2>/dev/null | sort -rn | head -n 15 | awk '{printf "directory\t%s\t%s\n", $2, $1}' || true
sudo find /var/log -type f -printf 'log\t%p\t%s\n' 2>/dev/null | sort -t "$(printf '\t')" -k3 -rn | head -n 20 || true
