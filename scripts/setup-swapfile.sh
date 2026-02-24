#!/bin/bash
# =============================================================================
# Creates a 4GB swap file and configures swappiness for a low-spec VPS.
#
# To remove swap:
#   swapoff /swapfile
#   rm /swapfile
#   sed -i '/\/swapfile/d' /etc/fstab
#   sed -i '/vm.swappiness/d' /etc/sysctl.conf
# =============================================================================

set -e

# Check if swap is already configured
if [ -f "/swapfile" ]; then
    echo "Swap already exists, skipping."
    free -h
    exit 0
fi

echo "[$(date)] Setting up swap..."

# Create a 4GB swap file
echo "Creating 4GB swap file..."
fallocate -l 4G /swapfile

# Secure it — only root should read/write it
chmod 600 /swapfile

# Format it as swap
mkswap /swapfile

# Enable it
swapon /swapfile

# Make it permanent across reboots
echo '/swapfile none swap sw 0 0' >> /etc/fstab

# Reduce swappiness — use RAM first, fall back to swap only when needed
echo "Configuring swappiness..."
sysctl vm.swappiness=10
echo 'vm.swappiness=10' >> /etc/sysctl.conf

echo "[$(date)] Done. Current memory status:"
free -h
