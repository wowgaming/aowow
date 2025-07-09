# encoding: utf-8

print("Starting auto-update.py")

# from time import sleep
# from selenium import webdriver
# from selenium.webdriver.support.ui import WebDriverWait
# from selenium.common.exceptions import TimeoutException
# from selenium.webdriver.support.ui import WebDriverWait
# from selenium.webdriver.support import expected_conditions as ec
# from selenium.webdriver.common.by import By
# import chromedriver_autoinstaller
# from pyvirtualdisplay import Display
# import os
# import sys

# print("Starting virtual display")

# display = Display(visible=0, size=(800, 800))
# display.start()

# print("Installing chromedriver via autoinstaller")

# chromedriver_autoinstaller.install()  # Check if the current version of chromedriver exists
#                                       # and if it doesn't exist, download it automatically,
#                                       # then add chromedriver to path

# print("chromedriver options")

# chrome_options = webdriver.ChromeOptions()
# options = [
#    "--window-size=1200,1200",
#     "--ignore-certificate-errors",
#     "--headless",
#     "--disable-gpu",
#     "--disable-extensions",
#     "--no-sandbox",
#     "--disable-dev-shm-usage",
#     '--remote-debugging-port=9222'
# ]

# for option in options:
#     chrome_options.add_argument(option)

# password = sys.argv[1]

# def wait_until(value, byval=By.ID) -> None:
#     try:
#         WebDriverWait(driver, 5000).until(
#             ec.presence_of_element_located((byval, value)))
#     except TimeoutException:
#         print("error connection")

# print("Starting chrome")
# driver = webdriver.Chrome(options=chrome_options)

# print("Visiting altervista")
# driver.get("https://aa.altervista.org/index.php?client_id=altervista&response_type=code&lang=it&redirect_uri=http%3A%2F%2Fit.altervista.org%2Fcplogin.php")

# print("Login")
# wait_until("username", By.NAME)
# wait_until("password", By.NAME)
# wait_until("button", By.TAG_NAME)
# sleep(1)

# driver.find_element(By.NAME, "username").send_keys("wowgaming")
# driver.find_element(By.NAME, "password").send_keys(password)
# driver.find_element(By.TAG_NAME, "button").click()
# print("submit credentials")

# sleep(5)

# print("Go to resources")
# a_elements = driver.find_elements(By.TAG_NAME, "a")
# for a in a_elements:
#     if a.get_attribute("href") and a.get_attribute("href").find("db.pl?sid=") > -1:
#         a.click()
#         break


# sleep(5)
# print("Go to tools/backup")
# a_elements = driver.find_elements(By.TAG_NAME, "a")
# for a in a_elements:
#     if a.get_attribute("href") and a.get_attribute("href").find("tools/backup/mysql_dump.pl?sid=") > -1:
#         a.click()
#         break

# print("Wait until dump")
# wait_until("dump", By.NAME)

# print("Wait until start")
# wait_until("start")

# print("Uploading aowow_update.sql")
# driver.find_element(By.NAME, "dump").send_keys(os.getcwd()+"/aowow_update.sql.zip")
# sleep(3)
# driver.find_element(By.ID, "start").click()

# print("Waiting 300s")
# sleep(300)

# print("aowow_update.sql loaded!")

# print("Uploading acore_world.sql")
# driver.find_element(By.NAME, "dump").send_keys(os.getcwd()+"/acore_world.sql.zip")
# sleep(3)
# driver.find_element(By.ID, "start").click()

# print("Waiting 300s")
# sleep(300)
# print("acore_world.sql loaded!")

# driver.close()
# driver.quit()

print("DONE!")
