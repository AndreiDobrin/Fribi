from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.common.by import By
from selenium.webdriver.support import expected_conditions as EC
from selenium.common.exceptions import TimeoutException, StaleElementReferenceException
from bs4 import BeautifulSoup
import time
import os
import urllib.parse as urlparse # Added this library to parse the URL
import gc

import mysql.connector
from mysql.connector import Error

# --- UPDATED CONNECTION LOGIC ---
if 'JAWSDB_URL' in os.environ:
    # Parse the URL just like PHP does
    jawsdb_url = urlparse.urlparse(os.environ.get('JAWSDB_URL'))
    
    db_host = jawsdb_url.hostname
    db_user = jawsdb_url.username
    db_pass = jawsdb_url.password
    db_name = jawsdb_url.path[1:]
    db_port = jawsdb_url.port or 3306
    time_sleep = 24*3600
else:
    # Local machine fallback
    db_host = 'localhost'
    db_user = 'root'
    db_pass = ''
    db_name = 'andrei'
    db_port = 3307
    time_sleep = 0
# -------------------------------



while True:
    try:
        # 1. Establish the connection
        connection = mysql.connector.connect(
                host=db_host,
                port=db_port,
                database=db_name,
                user=db_user,
                password=db_pass
            )

        if connection.is_connected():
            db_info = connection.get_server_info()
            print(f"Connected to MySQL Server version {db_info}")

            cursor = connection.cursor()
            cursor.execute("select database();")
            record = cursor.fetchone()
            print(f"You're connected to database: {record}")

            # PRODUCTS FETCH
            cursor = connection.cursor()
            cursor.execute("SELECT * FROM product")
            db_cache = {}
            for row in cursor:
                key = (row[4], row[8])
                db_cache[key] = row

            cursor.close()
            print(f"Loaded {len(db_cache)} products into cache.")
            
            def price_format(price): # formatare pret din "Pret: 11 lei si 99 bani" in "11.99". Este un format ciudatel la html pt pret si am ales metoda asta pentru scraping eficient
                string = price['aria-label'].upper()
                alphabet = [x for x in range(ord('A'), ord('Z')+1)]
                string = string.split()

                copy = []
                for x in range(0,len(string)):
                    if ord(string[x][0]) not in alphabet:
                        copy.append(string[x])
                price = '.'.join(copy)
                return price

            # LINK CATEGORII
            links = ("https://www.mega-image.ro/Fructe-si-legume-proaspete/c/001", "https://www.mega-image.ro/Lactate-si-oua/c/002", "https://www.mega-image.ro/Mezeluri-carne-si-ready-meal/c/003", "https://www.mega-image.ro/Produse-congelate/c/004", "https://www.mega-image.ro/Paine-cafea-cereale-si-mic-dejun/c/005", "https://www.mega-image.ro/Dulciuri-si-snacks/c/006", "https://www.mega-image.ro/Ingrediente-culinare/c/007", "https://www.mega-image.ro/Apa-si-sucuri/c/008", "https://www.mega-image.ro/Bauturi-si-tutun/c/009", "https://www.mega-image.ro/Natural-and-sanatos/c/010", "https://www.mega-image.ro/Curatenie-si-nealimentare/c/013", "https://www.mega-image.ro/Mama-si-ingrijire-copil/c/011", "https://www.mega-image.ro/Cosmetice-si-ingrijire-personala/c/012", "https://www.mega-image.ro/Animale-de-companie/c/014")

            # DRIVER OPTIONS
            chrome_options = Options()
            chrome_options.add_argument("--headless") 
            chrome_options.add_argument("--disable-dev-shm-usage") 
            chrome_options.add_argument("--no-sandbox") 
            chrome_options.add_argument("--window-size=1920,1080")
            chrome_options.add_argument("--disable-gpu")
            
            # BLOCK IMAGES & CSS
            prefs = {
                "profile.managed_default_content_settings.images": 2, 
                "profile.managed_default_content_settings.stylesheets": 2,
                "profile.managed_default_content_settings.fonts": 2
            }
            chrome_options.add_experimental_option("prefs", prefs)
            
            # driver.get("https://www.mega-image.ro")
            
            id_category = 1
            
            for category_link in links:
                driver = webdriver.Chrome(options=chrome_options)
                wait = WebDriverWait(driver, 10)
                try:
                    reject_button = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, '[data-testid="cookie-popup-reject"]')))
                    reject_button.click()
                except:
                    pass
                # id categorie
                driver.get(category_link)
                
                # INFINITE SCROLL
                last_height = driver.execute_script("return document.body.scrollHeight")
                wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, 'div[data-testid="loading-spinner-animation"]')))
                element = driver.find_element(By.CSS_SELECTOR, 'div[data-testid="loading-spinner-animation"]')
                while True:
                    driver.execute_script("window.scrollTo(0, document.body.scrollHeight-2000);")
                    time.sleep(1)
                    # <div class="sc-10qmhkn-1 euJzfh" data-testid="loading-spinner-animation"></div>
                    # .sc-10qmhkn-1
                    #if EC.presence_of_element_located((By.CSS_SELECTOR, 'div[data-testid]="loading-spinner"] > div[data-testid="loading-spinner-animation"]')):
                    #    print("da")
                    #else:
                    #    print("nu")
                    # EC.invisibility_of_element((By.CSS_SELECTOR, 'div[data-testid]="loading-spinner"] > div[data-testid="loading-spinner-animation"]'))
                    new_height = driver.execute_script("return document.body.scrollHeight")

                    if new_height == last_height:
                        try:
                            WebDriverWait(1, driver).until(
                                        EC.any_of(
                                            EC.staleness_of(element),
                                            EC.invisibility_of_element_located((By.CSS_SELECTOR, 'div[data-testid="loading-spinner-animation"]'))
                                        )
                                    )
                            print("Reached the end of the page...")
                            break
                        except TimeoutException:
                            print("elementul inca exista")
                        except:
                            print("da?")
                            break

                    last_height = new_height
                    print(f"Scrolled to: {new_height}")


                # salveaza codul html
                html = driver.page_source
                driver.quit()
                soup = BeautifulSoup(html, 'html.parser')
                del html
                gc.collect()

                items = soup.find_all(attrs={"data-testid": "product-block"})
                
                # fiecare produs gasit PE SITE
                for item in items:
                    driver = webdriver.Chrome(options=chrome_options)
                    wait = WebDriverWait(driver, 10)
                    try:
                        reject_button = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, '[data-testid="cookie-popup-reject"]')))
                        reject_button.click()
                    except:
                        pass
                    link = item.find(attrs={"data-testid": "product-block-image-link"})['href']
                    print(link)
                    driver.get("https://www.mega-image.ro" + link)
                    
                    try:
                        wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, 'div[data-testid="product-common-header-title"]'))) # in loc de time.sleep(), asteapta pana apare in DOM elementul cu titlu, pentru a fi mai rapid
                    except Exception as e:
                        print(e)
                    

                    item_html = driver.page_source
                    item_soup = BeautifulSoup(item_html, 'html.parser')
                    
                    # NUME PRODUS [4] PRODUCT_NAME
                    name = item.find(attrs={"data-testid": "product-name"}).text.strip()
                    # BRAND PRODUS [8] PRODUCT_BRAND
                    brand = item.find(attrs={"data-testid": "product-brand"}).text.strip()
                        
                    # PRET PRODUS [2] PRICE
                    try:
                        price = price = price_format(item.find(attrs={"data-testid": "product-block-price"}))
                    except Exception as e:
                        price = None
                        print(f"Price not found... Product:\n{brand} {name}\n{link}\n\033[31m\033[44m{e}\033[0m\n")
                    # offer PRODUS [3] OFFER
                    try:
                        offer = item_soup.select('div[data-testid="tag-label"]')[0].text.strip() #reducere produs (CONNECT, flat % sau reducere la cumpararea a mai multor produse de acelasi fel)
                        print(offer) 
                    except Exception as e:
                        offer = None
                    
                    # DESCRIERE PRODUS [5] description
                    try:
                        description = item_soup.find(class_ = "sc-e3oax-36").text.strip()
                        print(description)
                    except Exception as e:
                        description = None
                        print(f"Description section not found...  Product:\n{brand} {name}\n{link}\n")
                    # INGREDIENTE PRODUS [6] INGREDIENTS
                    try:
                        # wait for ingredients section (nu toate produsele au)
                        WebDriverWait(driver, 1).until(
                            EC.presence_of_element_located((By.CSS_SELECTOR, 'div[data-testid="accordion-item-ingredients"] > div.sc-45z6bh-1.kTTCfu > div.sc-14mbxjb-0.hDQks'))
                        )
                        ingredients = item_soup.select('div[data-testid="accordion-item-ingredients"] > div.sc-45z6bh-1.kTTCfu > div.sc-14mbxjb-0.hDQks')[0].text.strip()
                        print(ingredients)
                    except Exception as e:
                        ingredients = None
                        print(f"Ingredients section not found... Product:\n{brand} {name}\n{link}\n")
                        
                    # IMAGINE PRODUS [7] IMAGE_SRC
                    try:
                        image_src = item.find(attrs={"data-testid": "product-block-image"})['src']
                    except Exception as e:
                        image_src = None
                        print(f"Image source not found... Product:\n{brand} {name}\n{link}\n\033[31m\033[44m{e}\033[0m\n")
                    
                        
                    # PRICE_PER_UNIT [9] PRICE_PER_UNIT
                    # UNIT (KG/L/BUC) [10] UNIT
                    try:
                        price_per_unit = item.find(attrs={"data-testid": "product-block-price-per-unit"}).text.strip() #pret per kg/l/buc produs
                        for x in range(0,len(price_per_unit)):
                            if price_per_unit[x] == ',':
                                price_per_unit = price_per_unit.replace(',','.') # [9]
                                break
                        
                        unit = price_per_unit[price_per_unit.find('/')+1:] #aflare daca e kg sau litru [10]
                        price_per_unit = price_per_unit[:price_per_unit.find(' ')]
                    except Exception as e:
                        price_per_unit = None
                        print(f"Price per unit not found... Product:\n{brand} {name}\n{link}\n\033[31m\033[44m{e}\033[0m\n")
                    if (name, brand) in db_cache:
                        print("Article found")
                        
                        item_in_list_form = ["id", id_category, price, offer , name, description, ingredients, image_src, brand, price_per_unit, unit]
                        print(item_in_list_form[1:])
                        #product = db_cache[(name,brand)]
                        #key = (brand, name)
                        if float(db_cache[(name,brand)][2]) != float(price) or db_cache[(name,brand)][3] != offer or db_cache[(name,brand)][4] != name or db_cache[(name,brand)][5] != description or db_cache[(name,brand)][6] != ingredients or db_cache[(name,brand)][7] != image_src or db_cache[(name,brand)][8] != brand or float(db_cache[(name,brand)][9]) != float(price_per_unit) or db_cache[(name,brand)][10] != unit:
                            ok = 0
                            if float(db_cache[(name,brand)][2]) != float(price):
                                print(f"Pretul difera: {float(db_cache[(name,brand)][2])} vs {float(price)}")
                            if db_cache[(name,brand)][3] != offer:
                                print(f"Promotia difera: {db_cache[(name,brand)][3]} vs {offer}")
                            if db_cache[(name,brand)][4] != name:
                                print(f"Numele difera: {db_cache[(name,brand)][4]} vs {name}")
                            if db_cache[(name,brand)][5] != description:
                                print(f"Descrierea difera: {db_cache[(name,brand)][5]} vs {description}")
                            if db_cache[(name,brand)][6] != ingredients:
                                print(f"Ingredientele difera: {db_cache[(name,brand)][6]} vs {ingredients}")
                            if db_cache[(name,brand)][7] != image_src:
                                print(f"Sursa imaginii difera: {db_cache[(name,brand)][7]} vs {image_src}")
                            if db_cache[(name,brand)][8] != brand:
                                print(f"Brand-ul difera: {db_cache[(name,brand)][8]} vs {brand}")
                            if float(db_cache[(name,brand)][9]) != float(price_per_unit):
                                print(f"PPU difera: {float(db_cache[(name,brand)][9])} vs {float(price_per_unit)}")
                            if db_cache[(name,brand)][10] != unit:
                                print(f"Unitatea difera: {db_cache[(name,brand)][10]} vs {unit}")
                            cursor = connection.cursor()
                            sql = "UPDATE product SET price = %s, offer = %s, product_name = %s, product_description = %s, ingredients = %s, image_src = %s, product_brand = %s, price_per_unit = %s, unit = %s WHERE id_category = %s AND product_name = %s AND product_brand = %s"
                            val = (price, offer, name, description, ingredients, image_src, brand, price_per_unit, unit, id_category, name, brand)
                            print(val)
                            cursor.execute(sql, val)
                            connection.commit()
                            print(f"Article {brand} {name} already in DB; details have been modified...")
                        else:
                            print(f"Article {brand} {name} already in DB; details have NOT been modified...") 
                    else:
                        print("Article not found")
                        print(f"{brand} {name}: \n {price} ({price_per_unit})")
                        cursor = connection.cursor()
                        sql = "INSERT INTO product (product_name, product_description, ingredients, product_brand, price, image_src, price_per_unit, unit, offer, id_category, active) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)"
                        val = (name, description, ingredients, brand, price, image_src, price_per_unit, unit, offer, id_category, 1)
                        cursor.execute(sql, val)
                        connection.commit()
                        print(f"Record inserted. ID: {cursor.lastrowid}")
                        cursor.close()
                        
                    print("\n")
                    driver.quit()
                    
                print(len(items))
                id_category += 1 # contorizare id categorie


    except Error as e:
        print(f"Error while connecting to MySQL: {e}")

    finally:
        # Always close the connection
        if connection.is_connected():
            cursor.close()
            connection.close()
            print("MySQL connection is closed")

    if time_sleep != 0:
        driver.quit()
        time.sleep(time_sleep)
    else:
        driver.quit()
        break
    

