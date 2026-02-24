#!/bin/bash
# =============================================================================
# Fetches current Cloudflare IP ranges and updates UFW rules accordingly.
# Run this once during VPS setup, then weekly via cron to stay up to date.
#
# Cron (weekly, as root):
#   0 0 * * 0 /app/scripts/update-cloudflare-ips.sh >> /var/log/update-cloudflare-ips.log 2>&1
# =============================================================================

set -e

echo "[$(date)] Updating Cloudflare IP ranges..."

# Remove existing Cloudflare UFW rules
echo "Removing old Cloudflare rules..."
ufw status numbered | grep 'Cloudflare' | awk -F'[][]' '{print $2}' | sort -rn | while read num; do
    ufw --force delete "$num"
done

# Add IPv4 ranges
echo "Adding IPv4 ranges..."
for ip in $(curl -s https://www.cloudflare.com/ips-v4); do
    ufw allow from "$ip" to any port 80,443 proto tcp comment 'Cloudflare'
done

# Add IPv6 ranges
echo "Adding IPv6 ranges..."
for ip in $(curl -s https://www.cloudflare.com/ips-v6); do
    ufw allow from "$ip" to any port 80,443 proto tcp comment 'Cloudflare'
done

# Block direct access to ports 80 and 443 from everyone else
ufw deny 80
ufw deny 443

# Reload UFW to apply changes
ufw reload

echo "[$(date)] Done. Current UFW status:"
ufw status verbose
