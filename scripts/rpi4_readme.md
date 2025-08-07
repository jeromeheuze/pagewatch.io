# PageWatch.io Raspberry Pi 4 Worker Setup

## 🍓 Raspberry Pi 4 High-Performance Worker

This guide sets up a PageWatch.io screenshot worker on Raspberry Pi 4. The RPi4 worker is optimized for higher performance with Chromium browser, enhanced monitoring, and better resource management.

## 📋 Prerequisites

- **Raspberry Pi 4** (4GB+ RAM recommended)
- **Raspberry Pi OS** (64-bit recommended)
- **Internet connection**
- **sudo access**
- **PageWatch.io server components** deployed

## 🚀 Quick Setup

### 1. Download and Run Setup Script

```bash
# Download the RPi4-specific setup script
curl -O https://pagewatch.io/scripts/setup-pagewatch-rpi4.sh
chmod +x setup-pagewatch-rpi4.sh

# Run the automated setup
./setup-pagewatch-rpi4.sh
```

### 2. Manual Setup (Alternative)

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install Chromium (Chrome not available for ARM64)
sudo apt install -y chromium-browser chromium-driver

# Install system dependencies
sudo apt install -y python3 python3-pip python3-venv python3-full git curl wget htop

# Create worker directory with virtual environment
mkdir -p ~/pagewatch-worker
cd ~/pagewatch-worker
python3 -m venv venv
source venv/bin/activate

# Install Python packages in virtual environment
pip install --upgrade pip
pip install selenium requests psutil

# Download worker script
wget -O worker.py https://pagewatch.io/scripts/rpi4-worker.py
chmod +x worker.py
```

## 🧪 Testing

### Test Dependencies
```bash
cd ~/pagewatch-worker
source venv/bin/activate

# Test Python packages
python3 -c "import selenium, requests, psutil; print('All dependencies OK!')"

# Test Chrome
google-chrome --version

# Test ChromeDriver
chromedriver --version
```

### Performance Test
```bash
# Run performance benchmarks
cd ~/pagewatch-worker
source venv/bin/activate
python3 test-performance.py
```

Expected output:
```
🧪 Testing screenshot performance...
✅ https://example.com: 12.3s (156789 bytes)
✅ https://github.com: 15.7s (234567 bytes)
✅ https://stackoverflow.com: 18.2s (345678 bytes)

📊 Average: 15.4s, 245KB
```

### Test Worker
```bash
# Test manually first
cd ~/pagewatch-worker
source venv/bin/activate
python3 worker.py

# Expected output:
# INFO - RPi4 Worker rpi4-XXXXX initialized
# INFO - Worker registered successfully
# INFO - Worker ready - polling for jobs...
```

## 🎛️ Service Management

### Create and Start Service
```bash
# Create systemd service
sudo tee /etc/systemd/system/pagewatch-rpi4-worker.service > /dev/null << 'EOF'
[Unit]
Description=PageWatch.io RPi4 Screenshot Worker
After=network.target

[Service]
Type=simple
User=pi
WorkingDirectory=/home/pi/pagewatch-worker
ExecStart=/home/pi/pagewatch-worker/venv/bin/python /home/pi/pagewatch-worker/worker.py
Restart=always
RestartSec=15
StandardOutput=journal
StandardError=journal
Environment=DISPLAY=:0
MemoryMax=2G
CPUQuota=200%

[Install]
WantedBy=multi-user.target
EOF

# Enable and start
sudo systemctl daemon-reload
sudo systemctl enable pagewatch-rpi4-worker.service
sudo systemctl start pagewatch-rpi4-worker.service
```

### Monitor Service
```bash
# Enhanced monitoring
python3 monitor.py

# Live logs
journalctl -u pagewatch-rpi4-worker -f

# Service status
systemctl status pagewatch-rpi4-worker
```

## 📊 Enhanced Monitoring

The RPi4 worker includes advanced monitoring:

### System Health Monitoring
```bash
python3 monitor.py
```

Output:
```
🍓 PageWatch RPi4 Worker Monitor
========================================
Status: 🟢 Active
Device: raspberrypi (Raspberry Pi 4)
Temperature: 42.3°C
Memory: 45.2% used (1.87GB available)
CPU Load: 1.23 (4 cores)
Jobs (1h): 12 completed, 0 failed

🔍 Health Check:
✅ Temperature OK: 42.3°C
✅ Memory usage OK: 45.2%
```

### Performance Stats
The worker logs performance metrics every 5 minutes:
```
📊 Performance Stats - Uptime: 3600s, Jobs: 24, Failed: 1
🌡️ System - Temp: 45.2°C, Memory: 48.3%, CPU: 23.1%
```

## ⚡ RPi4-Specific Features

### Enhanced Performance
- **Google Chrome**: Better rendering than Chromium
- **Higher memory limits**: 1024MB vs 256MB (NanoPi)
- **Faster polling**: 3-second intervals vs 5-second
- **Longer timeouts**: 45 seconds vs 30 seconds

### Advanced Monitoring
- **Temperature monitoring**: Prevents thermal throttling
- **Memory management**: Automatic health checks
- **Performance logging**: Detailed metrics every 5 minutes
- **System resource tracking**: CPU, memory, temperature

### Reliability Features
- **Virtual environment**: Isolated Python packages
- **Enhanced error handling**: Better failure recovery
- **Resource limits**: systemd memory and CPU quotas
- **Health checks**: Prevents processing during high load

## 🔧 Control Commands

```bash
# Service control
sudo systemctl start pagewatch-rpi4-worker     # Start worker
sudo systemctl stop pagewatch-rpi4-worker      # Stop worker
sudo systemctl restart pagewatch-rpi4-worker   # Restart worker
sudo systemctl status pagewatch-rpi4-worker    # Check status

# Monitoring
python3 monitor.py                             # System status
python3 test-performance.py                    # Performance test
journalctl -u pagewatch-rpi4-worker -f         # Live logs

# Maintenance
sudo systemctl disable pagewatch-rpi4-worker   # Disable auto-start
sudo systemctl enable pagewatch-rpi4-worker    # Enable auto-start
```

## 🔧 Troubleshooting

### Chrome Issues
```bash
# Test Chromium installation (not Chrome - ARM64 limitation)
chromium-browser --version
chromium-browser --headless --screenshot=/tmp/test.png https://example.com

# If Chromium fails, reinstall
sudo apt remove chromium-browser chromium-driver
sudo apt install -y chromium-browser chromium-driver
```

### Virtual Environment Issues
```bash
# Recreate virtual environment
cd ~/pagewatch-worker
rm -rf venv
python3 -m venv venv
source venv/bin/activate
pip install selenium requests psutil
```

### High Resource Usage
```bash
# Check system resources
htop

# Monitor temperature
watch -n 5 'cat /sys/class/thermal/thermal_zone0/temp | awk "{printf \"%.1f°C\n\", \$1/1000}"'

# Restart if overheating
sudo systemctl restart pagewatch-rpi4-worker
```

### API Connection Issues
```bash
# Test API endpoints
curl -X POST https://pagewatch.io/api/worker-heartbeat.php \
  -H "Content-Type: application/json" \
  -d '{"worker_id":"test","status":"online"}'

# Should return: {"success":true}
```

## 📁 File Structure

```
/home/pi/pagewatch-worker/
├── venv/                    # Python virtual environment
├── worker.py               # Main worker script
├── monitor.py              # System monitoring
├── test-performance.py     # Performance benchmarks
└── logs/                   # Local log files
```

## ⚙️ Configuration

### Worker Settings
- **API Base**: `https://pagewatch.io/api`
- **CDN Upload**: `https://la.storage.bunnycdn.com/pagewatch`
- **CDN Delivery**: `https://cdn.pagewatch.io`
- **Screenshot timeout**: 45 seconds
- **Memory limit**: 1024MB
- **Polling interval**: 3 seconds

### System Limits
- **Memory quota**: 2GB max
- **CPU quota**: 200% (2 cores max)
- **Temperature warning**: >70°C
- **Memory warning**: >80%

## 📈 Expected Performance

### Typical Screenshots
- **Simple pages**: 10-20 seconds
- **Complex pages**: 20-40 seconds
- **Heavy JS pages**: 30-60 seconds

### System Resources
- **Idle memory**: 20-30%
- **Processing memory**: 40-60%
- **Idle temperature**: 35-45°C
- **Processing temperature**: 45-60°C

### Throughput
- **Concurrent capacity**: 1 screenshot at a time
- **Daily capacity**: 500+ screenshots
- **Peak efficiency**: 2-3 screenshots per minute

## 🚨 Health Monitoring

### Automatic Health Checks
The worker automatically monitors:
- **Temperature**: Pauses if >75°C
- **Memory**: Pauses if >80% usage
- **CPU load**: Throttles on high load
- **Error rate**: Restarts driver on consecutive failures

### Warning Indicators
- 🟡 **Yellow**: Moderate resource usage (60-80%)
- ⚠️ **Orange**: High resource usage (80-90%)
- 🔴 **Red**: Critical usage (>90%) - worker pauses

## 🔗 Integration

### Worker Registration
The RPi4 worker automatically registers with:
- **Worker ID**: `raspberrypi-[unique-id]`
- **Device Type**: `raspberry_pi_4`
- **Enhanced stats**: Temperature, memory, CPU metrics

### Load Balancing
Works seamlessly with other workers:
- Polls same job queue as NanoPi
- Processes jobs in priority order
- Automatic failover between devices

## 📚 Advanced Usage

### Custom Configuration
Edit `/home/pi/pagewatch-worker/worker.py` to customize:
- Screenshot quality settings
- Timeout values
- Memory limits
- Polling intervals

### Multiple Workers
To run multiple RPi4 workers:
1. Copy setup to different directory
2. Change service name in systemd file
3. Use different log files
4. Start additional services

---

**RPi4 Worker Status**: High-performance screenshot processing ready! 🍓⚡