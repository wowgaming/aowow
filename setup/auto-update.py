# encoding: utf-8
from time import sleep
from selenium import webdriver
from selenium.webdriver.support.ui import WebDriverWait
from selenium.common.exceptions import TimeoutException
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as ec
from selenium.webdriver.common.by import By
import chromedriver_autoinstaller
from pyvirtualdisplay import Display
import os
import sys

display = Display(visible=0, size=(800, 800))
display.start()

chromedriver_autoinstaller.install()  # Check if the current version of chromedriver exists
                                      # and if it doesn't exist, download it automatically,
                                      # then add chromedriver to path

chrome_options = webdriver.ChromeOptions()
options = [
   "--window-size=1200,1200",
    "--ignore-certificate-errors"
    #"--headless",
    #"--disable-gpu",
    #"--window-size=1920,1200",
    #"--ignore-certificate-errors",
    #"--disable-extensions",
    #"--no-sandbox",
    #"--disable-dev-shm-usage",
    #'--remote-debugging-port=9222'
]

for option in options:
    chrome_options.add_argument(option)

password = sys.argv[1]

def wait_until(value, byval=By.ID) -> None:
    try:
        WebDriverWait(driver, 5000).until(
            ec.presence_of_element_located((byval, value)))
    except TimeoutException:
        print("error connection")

driver = webdriver.Chrome(options=chrome_options)

driver.get("https://aa.altervista.org/index.php?client_id=altervista&response_type=code&lang=it&redirect_uri=http%3A%2F%2Fit.altervista.org%2Fcplogin.php")

wait_until("username", By.NAME)
wait_until("password", By.NAME)
wait_until("button", By.TAG_NAME)
sleep(1)

driver.find_element(By.NAME, "username").send_keys("wowgaming")
driver.find_element(By.NAME, "password").send_keys(password)
driver.find_element(By.TAG_NAME, "button").click()

sleep(5)
a_elements = driver.find_elements(By.TAG_NAME, "a")
for a in a_elements:
    if a.get_attribute("href") and a.get_attribute("href").find("db.pl?sid=") > -1:
        a.click()
        break


sleep(5)
a_elements = driver.find_elements(By.TAG_NAME, "a")
for a in a_elements:
    if a.get_attribute("href") and a.get_attribute("href").find("tools/backup/mysql_dump.pl?sid=") > -1:
        a.click()
        break

wait_until("dump", By.NAME)
wait_until("start")

driver.find_element(By.NAME, "dump").send_keys(os.getcwd()+"/aowow_update.sql.zip")
sleep(3)
driver.find_element(By.ID, "start").click()
sleep(100)
print("aowow_update.sql loaded!")

driver.find_element(By.NAME, "dump").send_keys(os.getcwd()+"/acore_world.sql.zip")
sleep(3)
driver.find_element(By.ID, "start").click()
sleep(100)
print("acore_world.sql loaded!")

driver.close()
driver.quit()
