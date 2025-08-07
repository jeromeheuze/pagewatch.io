# PageWatch.io NanoPi Worker Setup

## 🔷 NanoPi Neo Worker Configuration

This guide sets up a PageWatch.io screenshot worker on NanoPi devices. The NanoPi worker uses a lightweight configuration optimized for ARM hardware with limited resources.

## 📋 Prerequisites

- **NanoPi Neo** (or compatible) running Debian/Ubuntu
- **Internet connection** 
- **Root/sudo access**
- **PageWatch.io server components** deployed

## 🚀 Quick Setup

### 1. Download and Run Setup Script

```bash
# Download the setup script
curl -O https://pagewatch.io/scripts/setup-pagewatch-nanopi-worker.sh
chmod +x setup-pagewatch-nanopi-worker.sh

# Run the automated setup
./setup-pagewatch-nanopi-worker.sh
```

### 2. Manual Setup (Alternative)

If the automated script fails, follow these manual steps:

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install dependencies
sudo apt install -y python3 python3-pip chromium chromium-driver git curl wget

# Install Python packages
pip3 install --break-system-packages selenium requests

# Create worker directory
mkdir -p ~/pagewatch-worker
cd ~/pagewatch-worker

# Download worker script
wget -O worker.py https://pagewatch.io/scripts/nanopi-worker.py
chmod +x worker.py

# Create systemd service
sudo tee /etc/systemd/system/pagewatch-worker.service > /dev/null << 'EOF'
[Unit]
Description=PageWatch.io Screenshot Worker
After=network.target

[Service]
Type=simple
User=root
WorkingDirectory=/root/pagewatch-worker
ExecStart=/usr/bin/python3 /root/pagewatch-worker/worker.py
Restart=always
RestartSec=10
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
EOF

# Enable and start service
sudo systemctl daemon-reload
sudo systemctl enable pagewatch-worker.service
```

## 🧪 Testing

### Test Dependencies
```bash
# Check Python dependencies
python3 -c "import selenium, requests; print('Dependencies OK')"

# Test Chromium
chromium --version
chromedriver --version

# Test screenshot capability
chromium --headless --screenshot=/tmp/test.png https://example.com
ls -la /tmp/test.png
```

### Test Worker
```bash
# Test manually first
cd ~/pagewatch-worker
python3 worker.py

# Expected output:
# INFO - Chrome driver initialized successfully
# INFO - Worker nanopineo-XXXXX initialized
# INFO - Worker registered successfully
```

## 🎛️ Service Management

### Start Worker Service
```bash
sudo systemctl start pagewatch-worker
sudo systemctl enable pagewatch-worker
```

### Monitor Worker
```bash
# Check status
python3 monitor.py

# View live logs
journalctl -u pagewatch-worker -f

# Check service status
systemctl status pagewatch-worker
```

### Control Commands
```bash
# Start worker
sudo systemctl start pagewatch-worker

# Stop worker
sudo systemctl stop pagewatch-worker

# Restart worker
sudo systemctl restart pagewatch-worker

# View recent logs
journalctl -u pagewatch-worker --lines=20
```

## 📊 Monitoring Output

The `monitor.py` script shows:
```
🔍 PageWatch Worker Monitor
==============================
Status: 🟢 Active
Temperature: 25.8°C
Memory Usage: 37.2%
Device: nanopineo

📋 Recent Logs:
INFO - Worker registered successfully
INFO - Processing job 1: https://example.com
INFO - Job 1 completed successfully
```

## ⚡ NanoPi-Specific Optimizations

### Performance Settings
- **Memory limit**: 256MB for Chrome
- **Screenshot timeout**: 30 seconds
- **Polling interval**: 5 seconds
- **Fallback method**: wkhtmltoimage for reliability

### Resource Management
- Uses system Python packages (no virtual environment)
- Minimal Chrome arguments for ARM compatibility
- Automatic driver restart on consecutive failures
- Temperature and memory monitoring

## 🔧 Troubleshooting

### Common Issues

**Worker won't start:**
```bash
# Check dependencies
python3 -c "import selenium, requests"

# Check Chrome installation
which chromium
which chromedriver
```

**Chrome driver errors:**
```bash
# Install chromium-driver if missing
sudo apt install -y chromium-driver

# Verify installation
chromedriver --version
```

**API connection issues:**
```bash
# Test API connection
curl -X POST https://pagewatch.io/api/worker-heartbeat.php \
  -H "Content-Type: application/json" \
  -d '{"worker_id":"test","status":"online"}'

# Expected response: {"success":true}
```

**High resource usage:**
```bash
# Monitor system resources
htop

# Check temperature
cat /sys/class/thermal/thermal_zone0/temp

# Restart worker if needed
sudo systemctl restart pagewatch-worker
```

## 📁 File Locations

- **Worker script**: `/root/pagewatch-worker/worker.py`
- **Logs**: `/tmp/pagewatch-worker.log`
- **Service**: `/etc/systemd/system/pagewatch-worker.service`
- **Monitor**: `/root/pagewatch-worker/monitor.py`

## 🔗 Configuration

The worker connects to:
- **API**: `https://pagewatch.io/api`
- **CDN Upload**: `https://la.storage.bunnycdn.com/pagewatch`
- **CDN Delivery**: `https://cdn.pagewatch.io`

## 📈 Expected Performance

- **Screenshot time**: 15-45 seconds
- **Memory usage**: 30-50%
- **Temperature**: 25-45°C
- **Uptime**: >99% (with auto-restart)

## 🆘 Support

If you encounter issues:
1. Check the logs: `journalctl -u pagewatch-worker -f`
2. Verify API connectivity
3. Monitor system resources
4. Restart the service if needed

---

**NanoPi Worker Status**: Ready for production screenshot processing! 🔷