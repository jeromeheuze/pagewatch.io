#!/usr/bin/env python3
"""
PageWatch.io Hardware Worker - NanoPi Optimized
"""

import requests
import json
import time
import uuid
import os
import tempfile
import subprocess
import logging
from datetime import datetime

# Configuration
API_BASE_URL = "https://pagewatch.io/api"
WORKER_ID = f"{os.uname().nodename}-{uuid.uuid4().hex[:8]}"
CDN_UPLOAD_URL = "https://la.storage.bunnycdn.com/pagewatch"
CDN_ACCESS_KEY = "6cac3ad1-1f4a-42f2-b4012d8a3120-1640-4584"
CDN_BASE_URL = "https://cdn.pagewatch.io"

# Setup logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler('/tmp/pagewatch-worker.log'),
        logging.StreamHandler()
    ]
)
logger = logging.getLogger(__name__)

class ScreenshotWorker:
    def __init__(self):
        self.driver = None
        self.browser_type = self.detect_browser()
        self.setup_driver()
        logger.info(f"Worker {WORKER_ID} initialized with {self.browser_type}")

    def detect_browser(self):
        """Detect available browser on the system"""
        browsers = [
            ('/usr/bin/chromium', 'chromium'),
            ('/usr/bin/google-chrome', 'chrome'),
            ('/usr/bin/firefox-esr', 'firefox'),
            ('/usr/bin/firefox', 'firefox')
        ]

        for path, browser_type in browsers:
            if os.path.exists(path):
                logger.info(f"Found browser: {browser_type} at {path}")
                return browser_type

        raise Exception("No supported browser found. Install chromium, chrome, or firefox.")

    def setup_driver(self):
        """Initialize WebDriver based on available browser"""
        try:
            if self.browser_type in ['chromium', 'chrome']:
                self.setup_chrome_driver()
            elif self.browser_type == 'firefox':
                self.setup_firefox_driver()

            self.driver.set_page_load_timeout(30)
            logger.info(f"{self.browser_type} driver initialized successfully")

        except Exception as e:
            logger.error(f"Failed to initialize {self.browser_type} driver: {e}")
            # Fallback to headless screenshot using wkhtmltopdf or similar
            self.setup_fallback_method()

    def setup_chrome_driver(self):
        """Setup Chrome/Chromium driver"""
        try:
            from selenium import webdriver
            from selenium.webdriver.chrome.options import Options

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
            options.add_argument('--memory-pressure-off')
            options.add_argument('--max_old_space_size=256')  # Lower for NanoPi

            # Try to find Chrome binary
            if os.path.exists('/usr/bin/chromium'):
                options.binary_location = '/usr/bin/chromium'
            elif os.path.exists('/usr/bin/google-chrome'):
                options.binary_location = '/usr/bin/google-chrome'

            self.driver = webdriver.Chrome(options=options)

        except Exception as e:
            logger.warning(f"Chrome setup failed: {e}")
            raise

    def setup_firefox_driver(self):
        """Setup Firefox driver as fallback"""
        try:
            from selenium import webdriver
            from selenium.webdriver.firefox.options import Options

            options = Options()
            options.add_argument('--headless')
            options.add_argument('--width=1920')
            options.add_argument('--height=1080')

            self.driver = webdriver.Firefox(options=options)

        except Exception as e:
            logger.warning(f"Firefox setup failed: {e}")
            raise

    def setup_fallback_method(self):
        """Setup fallback screenshot method using system tools"""
        logger.info("Setting up fallback screenshot method")
        self.driver = None
        self.use_fallback = True

        # Check if wkhtmltopdf is available
        try:
            subprocess.run(['which', 'wkhtmltopdf'], check=True, capture_output=True)
            logger.info("wkhtmltopdf available for fallback")
        except:
            logger.warning("Installing wkhtmltopdf for fallback screenshots")
            os.system("sudo apt install -y wkhtmltopdf")

    def take_screenshot_fallback(self, url):
        """Take screenshot using wkhtmltopdf as fallback"""
        try:
            temp_file = tempfile.NamedTemporaryFile(suffix='.png', delete=False)
            temp_file.close()

            cmd = [
                'wkhtmltoimage',
                '--width', '1920',
                '--height', '1080',
                '--format', 'png',
                '--quality', '90',
                url,
                temp_file.name
            ]

            result = subprocess.run(cmd, capture_output=True, text=True, timeout=60)

            if result.returncode == 0 and os.path.exists(temp_file.name):
                logger.info(f"Fallback screenshot saved to {temp_file.name}")
                return temp_file.name
            else:
                raise Exception(f"wkhtmltoimage failed: {result.stderr}")

        except Exception as e:
            raise Exception(f"Fallback screenshot error: {str(e)}")

    def register_worker(self):
        """Register this worker with the main server"""
        try:
            response = requests.post(f"{API_BASE_URL}/worker-register.php",
                json={
                    'worker_id': WORKER_ID,
                    'name': f"NanoPi Worker ({os.uname().nodename})",
                    'ip_address': self.get_local_ip()
                },
                timeout=10
            )
            if response.status_code == 200:
                logger.info("Worker registered successfully")
                return True
            else:
                logger.warning(f"Worker registration failed: {response.status_code} - {response.text}")
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

    def heartbeat(self):
        """Send heartbeat to main server"""
        try:
            response = requests.post(f"{API_BASE_URL}/worker-heartbeat.php",
                json={'worker_id': WORKER_ID, 'status': 'online'},
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
            elif response.status_code == 404:
                logger.warning("API endpoint not found - make sure server components are deployed")
            else:
                logger.warning(f"API response: {response.status_code} - {response.text}")

            return None

        except Exception as e:
            logger.error(f"Error getting job: {e}")
            return None

    def take_screenshot(self, url):
        """Take screenshot of URL and return local file path"""
        if hasattr(self, 'use_fallback') and self.use_fallback:
            return self.take_screenshot_fallback(url)

        try:
            logger.info(f"Taking screenshot of {url}")

            # Navigate to URL
            self.driver.get(url)

            # Wait for page to load
            if self.browser_type == 'firefox':
                time.sleep(5)  # Firefox needs more time
            else:
                from selenium.webdriver.support.ui import WebDriverWait
                WebDriverWait(self.driver, 15).until(
                    lambda driver: driver.execute_script("return document.readyState") == "complete"
                )

            # Additional wait for dynamic content
            time.sleep(3)

            # Set window size for consistent screenshots
            self.driver.set_window_size(1920, 1080)

            # Take screenshot
            temp_file = tempfile.NamedTemporaryFile(suffix='.png', delete=False)
            temp_file.close()

            if self.driver.save_screenshot(temp_file.name):
                logger.info(f"Screenshot saved to {temp_file.name}")
                return temp_file.name
            else:
                raise Exception("Failed to save screenshot")

        except Exception as e:
            logger.error(f"Screenshot failed: {e}")
            raise Exception(f"Screenshot error: {str(e)}")

    def upload_to_cdn(self, file_path, job_id):
        """Upload screenshot to CDNBunny"""
        try:
            filename = f"{job_id}-{int(time.time())}.png"
            upload_url = f"{CDN_UPLOAD_URL}/{filename}"

            headers = {
                'AccessKey': CDN_ACCESS_KEY,
                'Content-Type': 'image/png'
            }

            with open(file_path, 'rb') as f:
                response = requests.put(upload_url, data=f, headers=headers, timeout=60)

            if response.status_code in [200, 201]:
                cdn_url = f"{CDN_BASE_URL}/{filename}"
                logger.info(f"Screenshot uploaded to CDN: {cdn_url}")
                return cdn_url
            else:
                logger.error(f"CDN upload response: {response.status_code} - {response.text}")
                raise Exception(f"CDN upload failed: {response.status_code}")

        except Exception as e:
            logger.error(f"CDN upload error: {e}")
            raise Exception(f"CDN upload failed: {str(e)}")
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
            if response.status_code == 200:
                return True
            else:
                logger.error(f"Complete job response: {response.status_code} - {response.text}")
                return False
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
        """Process a single screenshot job"""
        job_id = job['id']
        url = job['url']

        logger.info(f"Processing job {job_id}: {url}")

        try:
            # Take screenshot
            screenshot_path = self.take_screenshot(url)

            # Upload to CDN
            cdn_url = self.upload_to_cdn(screenshot_path, job_id)

            # Mark as completed
            if self.complete_job(job_id, cdn_url):
                logger.info(f"Job {job_id} completed successfully")
            else:
                logger.error(f"Failed to mark job {job_id} as completed")

        except Exception as e:
            error_msg = str(e)
            logger.error(f"Job {job_id} failed: {error_msg}")
            self.fail_job(job_id, error_msg)

    def test_connection(self):
        """Test connection to API server"""
        try:
            response = requests.get(f"{API_BASE_URL}/worker-heartbeat.php", timeout=5)
            logger.info(f"API connection test: {response.status_code}")
            return response.status_code in [200, 405]  # 405 is OK for GET on POST endpoint
        except Exception as e:
            logger.error(f"API connection failed: {e}")
            return False

    def run(self):
        """Main worker loop"""
        logger.info(f"Starting worker {WORKER_ID}")

        # Test API connection first
        if not self.test_connection():
            logger.error("Cannot connect to API server. Make sure PageWatch.io API endpoints are deployed.")
            return

        # Register worker
        if not self.register_worker():
            logger.error("Failed to register worker. Continuing anyway...")

        consecutive_failures = 0
        max_consecutive_failures = 5
        last_heartbeat = 0

        while True:
            try:
                # Send heartbeat every 30 seconds
                current_time = int(time.time())
                if current_time - last_heartbeat >= 30:
                    if self.heartbeat():
                        last_heartbeat = current_time

                # Get next job
                job = self.get_next_job()

                if job:
                    consecutive_failures = 0
                    self.process_job(job)
                else:
                    # No jobs available, wait before polling again
                    time.sleep(5)

            except KeyboardInterrupt:
                logger.info("Worker shutdown requested")
                break
            except Exception as e:
                consecutive_failures += 1
                logger.error(f"Worker error #{consecutive_failures}: {e}")

                if consecutive_failures >= max_consecutive_failures:
                    logger.critical("Too many consecutive failures, restarting driver")
                    self.restart_driver()
                    consecutive_failures = 0

                time.sleep(10)  # Wait before retrying

        self.cleanup()

    def restart_driver(self):
        """Restart the WebDriver"""
        try:
            if self.driver:
                self.driver.quit()
        except:
            pass

        time.sleep(5)
        self.setup_driver()
        logger.info("Driver restarted")

    def cleanup(self):
        """Clean up resources"""
        try:
            if self.driver:
                self.driver.quit()
            logger.info("Worker cleanup completed")
        except Exception as e:
            logger.error(f"Cleanup error: {e}")

if __name__ == "__main__":
    try:
        worker = ScreenshotWorker()
        worker.run()
    except KeyboardInterrupt:
        print("\nWorker stopped by user")
    except Exception as e:
        logger.error(f"Worker startup failed: {e}")
        print(f"Error: {e}")
        print("\nTroubleshooting:")
        print("1. Make sure chromium or firefox is installed")
        print("2. Verify API endpoints are deployed on pagewatch.io")
        print("3. Check internet connectivity")
        print("4. Run: python3 -c 'import selenium, requests; print(\"Dependencies OK\")'")