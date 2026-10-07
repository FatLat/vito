FILE_PATH="{{ $path }}"
REAL_PATH=$(sudo realpath -e -- "$FILE_PATH")
case "$REAL_PATH" in
    /var/log/*) ;;
    *) echo "VITO_SSH_ERROR: $FILE_PATH is outside /var/log"; exit 1 ;;
esac
if ! sudo test -f "$REAL_PATH"; then
    echo "VITO_SSH_ERROR: $FILE_PATH is not a regular file"
    exit 1
fi
sudo truncate -s 0 -- "$REAL_PATH"
