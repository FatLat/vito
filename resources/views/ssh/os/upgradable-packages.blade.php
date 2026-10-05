sudo apt list --upgradable 2>/dev/null | tail -n +2 || true
if [ -f /var/run/reboot-required ]; then echo "VITO_REBOOT_REQUIRED"; fi
