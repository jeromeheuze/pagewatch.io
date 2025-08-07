#!/bin/bash
# setup-pagewatch-rpi4.sh
# Dedicated setup script for Raspberry Pi 4 workers

set -e

echo "🍓 Setting up PageWatch.io Worker for Raspberry Pi 4..."
echo "=========================================="

# Detect system info
echo "📊 System Information:"
echo "Device: $(cat /proc/device-tree/model 2>/dev/null || echo 'Unknown')"
echo "OS: $(lsb_release -d 2>/dev/null | cut -f2 || echo 'Unknown')"
echo "Memory: $(free -h | awk '/^Mem:/ {print $2}')"
echo "CPU: $(nproc) cores"
echo ""

# Update system
echo "📦 Updating system packages..."
sudo apt update && sudo apt upgrade -y

# Install Chromium (Chrome not available for ARM64)
echo "🌐 Installing Chromium browser for RPi4..."
sudo apt install -y chromium-browser chromium-driver

# Verify installation
echo "✅ Checking browser installation..."
chromium-browser --version
chromedriver --version

# Install additional dependencies
echo "🛠️ Installing system dependencies..."
sudo apt install -y \
    python3 \
    python3-pip \
    python3-venv \
    python3-full \
    git \
    curl \
    wget \
    unzip \
    htop \
    redis-tools

# Create virtual environment (better for RPi4)
echo "🐍 Setting up Python virtual environment..."
mkdir -p ~/pagewatch-worker
cd ~/pagewatch-worker

python3 -m venv venv
source venv/bin/activate

# Install Python packages in virtual environment
echo "📦 Installing Python packages..."
pip install --upgrade pip
pip install selenium requests psutil

# Create optimized worker script for RPi4
echo "📝 Creating RPi4 worker script..."
cat > worker.py << 'WORKER_SCRIPT_EOF'
#!/usr/bin/env python3
"""
PageWatch.io Hardware Worker - Raspberry Pi 4 Optimized
Enhanced version with better performance and monitoring for RPi4
"""

import requests
import json
import time
import uuid
import os
import tempfile
import subprocess
import logging
import psutil
from datetime import datetime

# Configuration
API_BASE_URL = "https://pagewatch.io/api"
WORKER_ID = f"{os.uname().nodename}-{uuid.uuid4().hex[:8]}"
CDN_UPLOAD_URL = "https://la.storage.bunnycdn.com/pagewatch"
CDN_ACCESS_KEY = "6cac3ad1-1f4a-42f2-b4012d8a3120-1640-4584"
CDN_BASE_URL = "https://cdn.pagewatch.io"

# RPi4 specific settings
CHROME_BINARY = "/usr/bin/google-chrome"
SCREENSHOT_TIMEOUT = 45  # Longer timeout for RPi4
MAX_MEMORY_PERCENT = 80  # Monitor memory usage

# Setup logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler('/tmp/pagewatch-rpi4-worker.log'),
        logging.StreamHandler()
    ]
)
logger = logging.getLogger(__name__)

class RPi4ScreenshotWorker:
    def __init__(self):
        self.driver = None
        self.setup_driver()
        self.stats = {
            'jobs_processed': 0,
            'jobs_failed': 0,
            'start_time': time.time()
        }
        logger.info(f"RPi4 Worker {WORKER_ID} initialized")

    def setup_driver(self):
        """Initialize Chrome WebDriver optimized for RPi4"""
        try:
            from selenium import webdriver
            from selenium.webdriver.chrome.options import Options
            from selenium.webdriver.chrome.service import Service

            options = Options()
            options.add_argument('--headless')
            options.add_argument('--no-sandbox')
            options.add_argument('--disable-dev-shm-usage')
            options.add_argument('--disable-gpu')
            options.add_argument('--window-size=1920,1080')
            options.add_argument('--disable-web-security')
            options.add_argument('--disable-features=VizDisplayCompositor')
            options.add_argument('--hide-scrollbars')
            options.add_argument('--disable-extensions')
            options.add_argument('--disable-background-timer-throttling')
            options.add_argument('--disable-renderer-backgrounding')
            options.add_argument('--disable-ipc-flooding-protection')

            # RPi4 optimizations - higher memory limits
            options.add_argument('--memory-pressure-off')
            options.add_argument('--max_old_space_size=1024')  # Higher for RPi4
            options.add_argument('--disable-backgrounding-occluded-windows')
            options.add_argument('--disable-background-timer-throttling')

            # Use Google Chrome if available
            if os.path.exists(CHROME_BINARY):
                options.binary_location = CHROME_BINARY
                logger.info("Using Google Chrome")
            elif os.path.exists('/usr/bin/chromium'):
                options.binary_location = '/usr/bin/chromium'
                logger.info("Using Chromium")

            self.driver = webdriver.Chrome(options=options)
            self.driver.set_page_load_timeout(SCREENSHOT_TIMEOUT)

            logger.info("Chrome driver initialized successfully")

        except Exception as e:
            logger.error(f"Failed to initialize Chrome driver: {e}")
            raise

    def get_system_stats(self):
        """Get RPi4 system statistics"""
        try:
            # CPU temperature
            temp = 0
            if os.path.exists('/sys/class/thermal/thermal_zone0/temp'):
                with open('/sys/class/thermal/thermal_zone0/temp', 'r') as f:
                    temp = int(f.read()) / 1000

            # Memory usage
            memory = psutil.virtual_memory()

            # CPU usage
            cpu_percent = psutil.cpu_percent(interval=1)

            return {
                'temperature': round(temp, 1),
                'memory_percent': round(memory.percent, 1),
                'memory_available_gb': round(memory.available / (1024**3), 2),
                'cpu_percent': round(cpu_percent, 1)
            }
        except Exception as e:
            logger.error(f"Error getting system stats: {e}")
            return {}

    def check_system_health(self):
        """Check if system is healthy enough to process jobs"""
        stats = self.get_system_stats()

        # Check memory usage
        if stats.get('memory_percent', 0) > MAX_MEMORY_PERCENT:
            logger.warning(f"High memory usage: {stats['memory_percent']}%")
            return False

        # Check temperature (RPi4 thermal throttling at 80°C)
        if stats.get('temperature', 0) > 75:
            logger.warning(f"High temperature: {stats['temperature']}°C")
            return False

        return True

    def register_worker(self):
        """Register this RPi4 worker with enhanced info"""
        try:
            stats = self.get_system_stats()

            response = requests.post(f"{API_BASE_URL}/worker-register.php",
                json={
                    'worker_id': WORKER_ID,
                    'name': f"RPi4 Worker ({os.uname().nodename})",
                    'ip_address': self.get_local_ip(),
                    'device_type': 'raspberry_pi_4',
                    'system_stats': stats
                },
                timeout=10
            )

            if response.status_code == 200:
                logger.info("RPi4 Worker registered successfully")
                logger.info(f"System stats: {stats}")
                return True
            else:
                logger.warning(f"Worker registration failed: {response.status_code}")
                return False

        except Exception as e:
            logger.error(f"Worker registration error: {e}")
            return False

    def get_local_ip(self):
        """Get local IP address"""
        try:
            import socket
            s = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
            s.connect(("8.8.8.8", 80))
            ip = s.getsockname()[0]
            s.close()
            return ip
        except:
            return "unknown"

    def enhanced_heartbeat(self):
        """Send enhanced heartbeat with system stats"""
        try:
            stats = self.get_system_stats()

            response = requests.post(f"{API_BASE_URL}/worker-heartbeat.php",
                json={
                    'worker_id': WORKER_ID,
                    'status': 'online',
                    'system_stats': stats,
                    'jobs_processed': self.stats['jobs_processed'],
                    'jobs_failed': self.stats['jobs_failed'],
                    'uptime': int(time.time() - self.stats['start_time'])
                },
                timeout=5
            )
            return response.status_code == 200
        except Exception as e:
            logger.error(f"Heartbeat failed: {e}")
            return False

    def get_next_job(self):
        """Poll for next job from the queue"""
        try:
            response = requests.post(f"{API_BASE_URL}/get-job.php",
                json={'worker_id': WORKER_ID},
                timeout=10
            )

            if response.status_code == 200:
                data = response.json()
                if data.get('success') and data.get('job'):
                    return data['job']
            return None

        except Exception as e:
            logger.error(f"Error getting job: {e}")
            return None

    def take_screenshot(self, url):
        """Take high-quality screenshot optimized for RPi4"""
        try:
            logger.info(f"Taking screenshot of {url}")

            # Navigate to URL
            self.driver.get(url)

            # Wait for page to load
            from selenium.webdriver.support.ui import WebDriverWait
            WebDriverWait(self.driver, 20).until(
                lambda driver: driver.execute_script("return document.readyState") == "complete"
            )

            # Additional wait for dynamic content and images
            time.sleep(5)  # Longer wait for RPi4 to ensure quality

            # Get page dimensions and optimize viewport
            total_height = self.driver.execute_script("return Math.max(document.body.scrollHeight, document.documentElement.scrollHeight)")
            viewport_height = min(max(1080, total_height), 8000)  # Cap at 8000px

            self.driver.set_window_size(1920, viewport_height)

            # Scroll to ensure all content is loaded
            self.driver.execute_script("window.scrollTo(0, document.body.scrollHeight/2);")
            time.sleep(1)
            self.driver.execute_script("window.scrollTo(0, 0);")
            time.sleep(1)

            # Take screenshot
            temp_file = tempfile.NamedTemporaryFile(suffix='.png', delete=False)
            temp_file.close()

            if self.driver.save_screenshot(temp_file.name):
                file_size = os.path.getsize(temp_file.name)
                logger.info(f"Screenshot saved: {temp_file.name} ({file_size} bytes)")
                return temp_file.name
            else:
                raise Exception("Failed to save screenshot")

        except Exception as e:
            logger.error(f"Screenshot failed: {e}")
            raise Exception(f"Screenshot error: {str(e)}")

    def upload_to_cdn(self, file_path, job_id):
        """Upload screenshot to CDNBunny with progress tracking"""
        try:
            filename = f"{job_id}-{int(time.time())}.png"
            upload_url = f"{CDN_UPLOAD_URL}/{filename}"

            headers = {
                'AccessKey': CDN_ACCESS_KEY,
                'Content-Type': 'image/png'
            }

            file_size = os.path.getsize(file_path)
            logger.info(f"Uploading {file_size} bytes to CDN...")

            with open(file_path, 'rb') as f:
                response = requests.put(upload_url, data=f, headers=headers, timeout=120)

            if response.status_code in [200, 201]:
                cdn_url = f"{CDN_BASE_URL}/{filename}"
                logger.info(f"Screenshot uploaded successfully: {cdn_url}")
                return cdn_url
            else:
                raise Exception(f"CDN upload failed: {response.status_code} - {response.text}")

        except Exception as e:
            logger.error(f"CDN upload error: {e}")
            raise
        finally:
            # Clean up local file
            try:
                os.unlink(file_path)
            except:
                pass

    def complete_job(self, job_id, cdn_url):
        """Mark job as completed"""
        try:
            response = requests.post(f"{API_BASE_URL}/complete-job.php",
                json={
                    'worker_id': WORKER_ID,
                    'job_id': job_id,
                    'cdn_url': cdn_url
                },
                timeout=10
            )
            return response.status_code == 200
        except Exception as e:
            logger.error(f"Error completing job: {e}")
            return False

    def fail_job(self, job_id, error_message):
        """Mark job as failed"""
        try:
            response = requests.post(f"{API_BASE_URL}/fail-job.php",
                json={
                    'worker_id': WORKER_ID,
                    'job_id': job_id,
                    'error_message': error_message
                },
                timeout=10
            )
            return response.status_code == 200
        except Exception as e:
            logger.error(f"Error failing job: {e}")
            return False

    def process_job(self, job):
        """Process a single screenshot job with enhanced logging"""
        job_id = job['id']
        url = job['url']
        start_time = time.time()

        logger.info(f"🎯 Processing job {job_id}: {url}")

        # Check system health before processing
        if not self.check_system_health():
            error_msg = "System health check failed - high resource usage"
            logger.error(error_msg)
            self.fail_job(job_id, error_msg)
            self.stats['jobs_failed'] += 1
            return

        try:
            # Take screenshot
            screenshot_path = self.take_screenshot(url)

            # Upload to CDN
            cdn_url = self.upload_to_cdn(screenshot_path, job_id)

            # Mark as completed
            if self.complete_job(job_id, cdn_url):
                processing_time = round(time.time() - start_time, 1)
                logger.info(f"✅ Job {job_id} completed in {processing_time}s")
                self.stats['jobs_processed'] += 1
            else:
                logger.error(f"Failed to mark job {job_id} as completed")

        except Exception as e:
            error_msg = str(e)
            processing_time = round(time.time() - start_time, 1)
            logger.error(f"❌ Job {job_id} failed after {processing_time}s: {error_msg}")
            self.fail_job(job_id, error_msg)
            self.stats['jobs_failed'] += 1

    def run(self):
        """Main worker loop with enhanced monitoring"""
        logger.info(f"🚀 Starting RPi4 worker {WORKER_ID}")

        # Register worker
        if not self.register_worker():
            logger.error("Failed to register worker. Continuing anyway...")

        consecutive_failures = 0
        max_consecutive_failures = 3
        last_heartbeat = 0
        last_stats_log = 0

        logger.info("🔄 Worker ready - polling for jobs...")

        while True:
            try:
                current_time = time.time()

                # Enhanced heartbeat every 30 seconds
                if current_time - last_heartbeat >= 30:
                    if self.enhanced_heartbeat():
                        last_heartbeat = current_time

                # Log stats every 5 minutes
                if current_time - last_stats_log >= 300:
                    self.log_performance_stats()
                    last_stats_log = current_time

                # Check system health periodically
                if not self.check_system_health():
                    logger.warning("⚠️ System health degraded, pausing for 60s")
                    time.sleep(60)
                    continue

                # Get next job
                job = self.get_next_job()

                if job:
                    consecutive_failures = 0
                    self.process_job(job)
                else:
                    # No jobs available, efficient polling
                    time.sleep(3)  # Faster polling for RPi4

            except KeyboardInterrupt:
                logger.info("🛑 Worker shutdown requested")
                break
            except Exception as e:
                consecutive_failures += 1
                logger.error(f"Worker error #{consecutive_failures}: {e}")

                if consecutive_failures >= max_consecutive_failures:
                    logger.critical("🔄 Too many failures, restarting driver")
                    self.restart_driver()
                    consecutive_failures = 0

                time.sleep(10)

        self.cleanup()

    def log_performance_stats(self):
        """Log performance statistics"""
        stats = self.get_system_stats()
        uptime = int(time.time() - self.stats['start_time'])

        logger.info(f"📊 Performance Stats - Uptime: {uptime}s, Jobs: {self.stats['jobs_processed']}, Failed: {self.stats['jobs_failed']}")
        logger.info(f"🌡️ System - Temp: {stats.get('temperature', 0)}°C, Memory: {stats.get('memory_percent', 0)}%, CPU: {stats.get('cpu_percent', 0)}%")

    def restart_driver(self):
        """Restart the WebDriver with cleanup"""
        try:
            if self.driver:
                self.driver.quit()
        except:
            pass

        # Clear any lingering Chrome processes
        try:
            subprocess.run(['pkill', '-f', 'chrome'], capture_output=True)
            subprocess.run(['pkill', '-f', 'chromium'], capture_output=True)
        except:
            pass

        time.sleep(5)
        self.setup_driver()
        logger.info("🔄 Driver restarted successfully")

    def cleanup(self):
        """Clean up resources"""
        try:
            if self.driver:
                self.driver.quit()
            logger.info("🧹 Worker cleanup completed")
        except Exception as e:
            logger.error(f"Cleanup error: {e}")

if __name__ == "__main__":
    try:
        worker = RPi4ScreenshotWorker()
        worker.run()
    except KeyboardInterrupt:
        print("\n🛑 Worker stopped by user")
    except Exception as e:
        logger.error(f"Worker startup failed: {e}")
        print(f"\n❌ Error: {e}")
        print("\n🔧 Troubleshooting:")
        print("1. Check Chrome: google-chrome --version")
        print("2. Test dependencies: python3 -c 'import selenium, requests, psutil'")
        print("3. Check API: curl https://pagewatch.io/api/worker-heartbeat.php")
        print("4. View logs: journalctl -u pagewatch-rpi4-worker -f")
WORKER_SCRIPT_EOF

chmod +x worker.py

# Create enhanced systemd service for RPi4
echo "🔧 Creating systemd service for RPi4..."
sudo tee /etc/systemd/system/pagewatch-rpi4-worker.service > /dev/null << 'SERVICE_EOF'
[Unit]
Description=PageWatch.io RPi4 Screenshot Worker
After=network.target
Wants=network-online.target

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

# Resource limits for RPi4
MemoryMax=2G
CPUQuota=200%

[Install]
WantedBy=multi-user.target
SERVICE_EOF

# Enable service
sudo systemctl daemon-reload
sudo systemctl enable pagewatch-rpi4-worker.service

# Create enhanced monitoring script
cat > monitor.py << 'MONITOR_EOF'
#!/usr/bin/env python3
"""
Enhanced monitoring script for RPi4 worker
"""
import subprocess
import time
import os
import json

def get_worker_status():
    try:
        result = subprocess.run(['systemctl', 'is-active', 'pagewatch-rpi4-worker'],
                              capture_output=True, text=True)
        return result.stdout.strip() == 'active'
    except:
        return False

def get_system_info():
    try:
        # CPU temperature
        temp = 0
        if os.path.exists('/sys/class/thermal/thermal_zone0/temp'):
            with open('/sys/class/thermal/thermal_zone0/temp', 'r') as f:
                temp = int(f.read()) / 1000

        # Memory info
        with open('/proc/meminfo', 'r') as f:
            meminfo = f.read()
            total = int([line for line in meminfo.split('\n') if 'MemTotal' in line][0].split()[1])
            available = int([line for line in meminfo.split('\n') if 'MemAvailable' in line][0].split()[1])
            used_percent = ((total - available) / total) * 100
            available_gb = available / 1024 / 1024

        # CPU info
        cpu_count = os.cpu_count()

        # Load average
        load1, load5, load15 = os.getloadavg()

        return {
            'temperature': round(temp, 1),
            'memory_used_percent': round(used_percent, 1),
            'memory_available_gb': round(available_gb, 2),
            'cpu_count': cpu_count,
            'load_1min': round(load1, 2)
        }
    except Exception as e:
        print(f"Error getting system info: {e}")
        return {}

def get_worker_stats():
    try:
        result = subprocess.run(['journalctl', '-u', 'pagewatch-rpi4-worker', '--since', '1 hour ago'],
                              capture_output=True, text=True)
        logs = result.stdout

        # Count completed jobs in last hour
        completed = logs.count('completed successfully')
        failed = logs.count('failed after')

        return {'completed_1h': completed, 'failed_1h': failed}
    except:
        return {'completed_1h': 0, 'failed_1h': 0}

if __name__ == "__main__":
    print("🍓 PageWatch RPi4 Worker Monitor")
    print("=" * 40)

    # Worker status
    is_active = get_worker_status()
    print(f"Status: {'🟢 Active' if is_active else '🔴 Inactive'}")

    # System info
    info = get_system_info()
    print(f"Device: {os.uname().nodename} (Raspberry Pi 4)")
    print(f"Temperature: {info.get('temperature', 0)}°C")
    print(f"Memory: {info.get('memory_used_percent', 0)}% used ({info.get('memory_available_gb', 0)}GB available)")
    print(f"CPU Load: {info.get('load_1min', 0)} ({info.get('cpu_count', 4)} cores)")

    # Worker performance
    stats = get_worker_stats()
    print(f"Jobs (1h): {stats['completed_1h']} completed, {stats['failed_1h']} failed")

    # Health warnings
    temp = info.get('temperature', 0)
    memory = info.get('memory_used_percent', 0)

    print("\n🔍 Health Check:")
    if temp > 70:
        print(f"⚠️  High temperature: {temp}°C (throttling risk)")
    elif temp > 60:
        print(f"🟡 Warm temperature: {temp}°C")
    else:
        print(f"✅ Temperature OK: {temp}°C")

    if memory > 80:
        print(f"⚠️  High memory usage: {memory}%")
    elif memory > 60:
        print(f"🟡 Moderate memory usage: {memory}%")
    else:
        print(f"✅ Memory usage OK: {memory}%")

    print("\n🔧 Commands:")
    print("• Start: sudo systemctl start pagewatch-rpi4-worker")
    print("• Stop: sudo systemctl stop pagewatch-rpi4-worker")
    print("• Logs: journalctl -u pagewatch-rpi4-worker -f")
    print("• Status: python3 monitor.py")
MONITOR_EOF

chmod +x monitor.py

# Create performance test script
cat > test-performance.py << 'PERF_TEST_EOF'
#!/usr/bin/env python3
"""
Performance test script for RPi4 worker
"""
import time
import tempfile
import os

def test_screenshot_speed():
    """Test screenshot performance"""
    print("🧪 Testing screenshot performance...")

    test_urls = [
        "https://example.com",
        "https://github.com",
        "https://stackoverflow.com"
    ]

    try:
        from selenium import webdriver
        from selenium.webdriver.chrome.options import Options

        options = Options()
        options.add_argument('--headless')
        options.add_argument('--no-sandbox')
        options.add_argument('--disable-dev-shm-usage')
        options.add_argument('--window-size=1920,1080')

        driver = webdriver.Chrome(options=options)
        driver.set_page_load_timeout(30)

        results = []

        for url in test_urls:
            start_time = time.time()

            try:
                driver.get(url)
                time.sleep(3)

                temp_file = tempfile.NamedTemporaryFile(suffix='.png', delete=False)
                temp_file.close()

                if driver.save_screenshot(temp_file.name):
                    duration = round(time.time() - start_time, 1)
                    size = os.path.getsize(temp_file.name)
                    results.append({'url': url, 'duration': duration, 'size': size})
                    print(f"✅ {url}: {duration}s ({size} bytes)")
                else:
                    print(f"❌ {url}: Failed to save")

                os.unlink(temp_file.name)

            except Exception as e:
                duration = round(time.time() - start_time, 1)
                print(f"❌ {url}: {duration}s - {e}")

        driver.quit()

        if results:
            avg_duration = sum(r['duration'] for r in results) / len(results)
            avg_size = sum(r['size'] for r in results) / len(results)
            print(f"\n📊 Average: {avg_duration}s, {round(avg_size/1024)}KB")

    except Exception as e:
        print(f"❌ Performance test failed: {e}")

if __name__ == "__main__":
    test_screenshot_speed()
PERF_TEST_EOF

chmod +x test-performance.py

echo ""
echo "✅ RPi4 Setup Complete!"
echo "======================"
echo ""
echo "📋 Next steps:"
echo "1. Test worker: python3 worker.py"
echo "2. Run performance test: python3 test-performance.py"
echo "3. Start service: sudo systemctl start pagewatch-rpi4-worker"
echo "4. Monitor: python3 monitor.py"
echo ""
echo "🔍 Key differences from NanoPi:"
echo "• Uses Google Chrome (better performance)"
echo "• Virtual environment for cleaner Python setup"
echo "• Enhanced system monitoring"
echo "• Higher memory limits and timeouts"
echo "• Performance testing included"
echo ""
echo "🌡️ RPi4 optimal operating conditions:"
echo "• Temperature: < 70°C"
echo "• Memory usage: < 80%"
echo "• CPU load: < 3.0"