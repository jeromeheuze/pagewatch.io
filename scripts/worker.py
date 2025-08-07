#!/usr/bin/env python3
"""
PageWatch.io Hardware Worker
Runs on NanoPi and Raspberry Pi 4 to process screenshot jobs
"""

import requests
import json
import time
import uuid
import os
import tempfile
from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.common.exceptions import TimeoutException, WebDriverException
import logging
from datetime import datetime

# Configuration
API_BASE_URL = "https://pagewatch.io/api"
WORKER_ID = f"{os.uname().nodename}-{uuid.uuid4().hex[:8]}"
CDN_UPLOAD_URL = "https://la.storage.bunnycdn.com/pagewatch"
CDN_ACCESS_KEY = "6cac3ad1-1f4a-42f2-b4012d8a3120-1640-4584"  # Replace with your actual key
CDN_BASE_URL = "https://cdn.pagewatch.io"  # Replace with your pull zone URL

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
        self.setup_driver()
        logger.info(f"Worker {WORKER_ID} initialized")

    def setup_driver(self):
        """Initialize Chrome WebDriver with optimal settings"""
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

        # Additional performance optimizations for Pi hardware
        options.add_argument('--memory-pressure-off')
        options.add_argument('--max_old_space_size=512')
        options.add_argument('--disable-background-timer-throttling')
        options.add_argument('--disable-renderer-backgrounding')

        try:
            self.driver = webdriver.Chrome(options=options)
            self.driver.set_page_load_timeout(30)
            logger.info("Chrome driver initialized successfully")
        except Exception as e:
            logger.error(f"Failed to initialize Chrome driver: {e}")
            raise

    def register_worker(self):
        """Register this worker with the main server"""
        try:
            response = requests.post(f"{API_BASE_URL}/worker-register.php",
                json={
                    'worker_id': WORKER_ID,
                    'name': f"Hardware Worker ({os.uname().nodename})",
                    'ip_address': self.get_local_ip()
                },
                timeout=10
            )
            if response.status_code == 200:
                logger.info("Worker registered successfully")
            else:
                logger.warning(f"Worker registration failed: {response.status_code}")
        except Exception as e:
            logger.error(f"Worker registration error: {e}")

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
            requests.post(f"{API_BASE_URL}/worker-heartbeat.php",
                json={'worker_id': WORKER_ID, 'status': 'online'},
                timeout=5
            )
        except Exception as e:
            logger.error(f"Heartbeat failed: {e}")

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
        """Take screenshot of URL and return local file path"""
        try:
            logger.info(f"Taking screenshot of {url}")

            # Navigate to URL
            self.driver.get(url)

            # Wait for page to load
            WebDriverWait(self.driver, 15).until(
                lambda driver: driver.execute_script("return document.readyState") == "complete"
            )

            # Additional wait for dynamic content
            time.sleep(3)

            # Get page dimensions and set window size
            total_height = self.driver.execute_script("return document.body.scrollHeight")
            self.driver.set_window_size(1920, max(1080, total_height))

            # Take screenshot
            temp_file = tempfile.NamedTemporaryFile(suffix='.png', delete=False)
            temp_file.close()

            if self.driver.save_screenshot(temp_file.name):
                logger.info(f"Screenshot saved to {temp_file.name}")
                return temp_file.name
            else:
                raise Exception("Failed to save screenshot")

        except TimeoutException:
            raise Exception("Page load timeout")
        except WebDriverException as e:
            raise Exception(f"WebDriver error: {str(e)}")
        except Exception as e:
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

    def run(self):
        """Main worker loop"""
        logger.info(f"Starting worker {WORKER_ID}")
        self.register_worker()

        consecutive_failures = 0
        max_consecutive_failures = 5

        while True:
            try:
                # Send heartbeat every few iterations
                if int(time.time()) % 30 == 0:
                    self.heartbeat()

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
    # Check for required dependencies
    try:
        from selenium import webdriver
        import requests
    except ImportError as e:
        print(f"Missing required dependency: {e}")
        print("Install with: pip3 install selenium requests")
        exit(1)

    worker = ScreenshotWorker()
    worker.run()