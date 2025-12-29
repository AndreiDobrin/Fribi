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

import mysql.connector
from mysql.connector import Error

# --- UPDATED CONNECTION LOGIC ---
if 'JAWSDB_URL' in os.environ:
    # Parse the URL just like PHP does
    jawsdb_url = urlparse.urlparse(os.environ.get('JAWSDB_URL'))
    
    db_host = jawsdb_url.hostname
    db_user = jawsdb_url.username
    db_pass = jawsdb_url.password
    db_name = jawsdb_url.path[1:] # Removes the leading '/'
    db_port = jawsdb_url.port or 3306 # Default to 3306 if not specified
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
            products = cursor.fetchall()
            cursor.close()


            def price_format(price): # formatare pret din "Pret: 11 lei si 99 bani" in "11.99". Este un format ciudatel la html pt pret si am ales metoda asta pentru scraping eficient
                string = price['aria-label'].upper()
                alphabet = [id_category for id_category in range(ord('A'), ord('Z')+1)]
                string = string.split()

                copy = []
                for id_category in range(0,len(string)):
                    if ord(string[id_category][0]) not in alphabet:
                        copy.append(string[id_category])
                price = '.'.join(copy)
                return price

            # LINK CATEGORII
            links = ("https://www.mega-image.ro/Fructe-si-legume-proaspete/c/001", "https://www.mega-image.ro/Lactate-si-oua/c/002", "https://www.mega-image.ro/Mezeluri-carne-si-ready-meal/c/003", "https://www.mega-image.ro/Produse-congelate/c/004", "https://www.mega-image.ro/Paine-cafea-cereale-si-mic-dejun/c/005", "https://www.mega-image.ro/Dulciuri-si-snacks/c/006", "https://www.mega-image.ro/Ingrediente-culinare/c/007", "https://www.mega-image.ro/Apa-si-sucuri/c/008", "https://www.mega-image.ro/Bauturi-si-tutun/c/009", "https://www.mega-image.ro/Natural-and-sanatos/c/010", "https://www.mega-image.ro/Curatenie-si-nealimentare/c/013", "https://www.mega-image.ro/Mama-si-ingrijire-copil/c/011", "https://www.mega-image.ro/Cosmetice-si-ingrijire-personala/c/012", "https://www.mega-image.ro/Animale-de-companie/c/014")

            # DRIVER OPTIONS
            chrome_options = Options()
            # chrome_options.add_argument("--headless") 
            chrome_options.add_argument("--disable-dev-shm-usage") 
            chrome_options.add_argument("--no-sandbox") 
            chrome_options.add_argument("--window-size=1920,1080")

            driver = webdriver.Chrome(options=chrome_options)
            wait = WebDriverWait(driver, 10)
            driver.get("https://www.mega-image.ro")
            try:
                reject_button = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, '[data-testid="cookie-popup-reject"]')))
                reject_button.click()
            except Exception:
                print("Cookie reject button not found")
            for category_link in links:
                id_category = 1 # id categorie
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
                            wait.until(
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

                # inchide selenium
                # driver.quit()

                # parser
                soup = BeautifulSoup(html, 'html.parser')

                items = soup.find_all(attrs={"data-testid": "product-block"})
                # fiecare produs gasit PE SITE
                for item in items:
                    
                    ok = 1 # verificare daca produs deja exista
                    
                    link = item.find(attrs={"data-testid": "product-block-image-link"})['href']
                    print(link)
                    driver.get("https://www.mega-image.ro" + link)
                    
                    wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, ".sc-e3oax-36.gArZOi"))) # in loc de time.sleep(), asteapta pana apare in DOM elementul cu descrierea, pentru a fi mai rapid

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
                        wait.until(
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
                        for id_category in range(0,len(price_per_unit)):
                            if price_per_unit[id_category] == ',':
                                price_per_unit = price_per_unit.replace(',','.') # [9]
                                break
                        
                        unit = price_per_unit[price_per_unit.find('/')+1:] #aflare daca e kg sau litru [10]
                        price_per_unit = price_per_unit[:price_per_unit.find(' ')]
                    except Exception as e:
                        price_per_unit = None
                        print(f"Price per unit not found... Product:\n{brand} {name}\n{link}\n\033[31m\033[44m{e}\033[0m\n")
                        
                    category_id = id_category
                                        
                    # fiecare produs din BAZA DE DATE
                    # DE FACUT QUERY PER CATEGORIE PENTRU EFICIENTA
                    for product in products:

                        if product[4] == name and product[8] == brand: # produsul a fost gasit. se verifica daca detaliile s-au schimbat
                            print(f"produsul a fost gasit")
                            '''
                            price = price_format(item.find(attrs={"data-testid": "product-block-price"}))
                            if price != product[2]:
                                ok = 2
                                break
                            '''


                            '''
                            old_price = "" #in caz de promotie, pretul produsului fara reducere
                            old_ppu = "" #in caz de promotie, pretul produsului per kg/l fara reducere
                            '''
                            item_in_list_form = ["id", id_category, price, offer , name, description, ingredients, image_src, brand, price_per_unit, unit]
                            print(item_in_list_form[1:])

                            if float(product[2]) != float(price) or product[3] != offer or product[4] != name or product[5] != description or product[6] != ingredients or product[7] != image_src or product[8] != brand or float(product[9]) != float(price_per_unit) or product[10] != unit:
                                
                                if float(product[2]) != float(price):
                                    print(f"Pretul difera: {float(product[2])} vs {float(price)}")
                                if product[3] != offer:
                                    print(f"Promotia difera: {product[3]} vs {offer}")
                                if product[4] != name:
                                    print(f"Numele difera: {product[4]} vs {name}")
                                if product[5] != description:
                                    print(f"Descrierea difera: {product[5]} vs {description}")
                                if product[6] != ingredients:
                                    print(f"Ingredientele difera: {product[6]} vs {ingredients}")
                                if product[7] != image_src:
                                    print(f"Sursa imaginii difera: {product[7]} vs {image_src}")
                                if product[8] != brand:
                                    print(f"Brand-ul difera: {product[8]} vs {brand}")
                                if float(product[9]) != float(price_per_unit):
                                    print(f"PPU difera: {float(product[9])} vs {float(price_per_unit)}")
                                if product[10] != unit:
                                    print(f"Unitatea difera: {product[10]} vs {unit}")

                                cursor = connection.cursor()
                                sql = "UPDATE product SET price = %s, offer = %s, product_name = %s, product_description = %s, ingredients = %s, image_src = %s, product_brand = %s, price_per_unit = %s, unit = %s WHERE id_category = %s AND product_name = %s AND product_brand = %s"
                                val = (price, offer, name, description, ingredients, image_src, brand, price_per_unit, unit, id_category, name, brand)
                                print(val)
                                cursor.execute(sql, val)
                                connection.commit()
                                print(f"Articolul {brand} {name} deja exista; au fost modificate detaliile sale...")
                                ok = -1
                            else:
                                ok = 0
                            break
                        
                    if ok == 0:
                        print(f"Articolul {brand} {name} deja exista; NU au fost modificate detaliile sale...") 
                    if ok == 1:
                        print("produsul nu a fost gasit")
                        '''
                        name = item.find(attrs={"data-testid": "product-name"}).text.strip() #nume produs [4]
                        brand = item.find(attrs={"data-testid": "product-brand"}).text.strip() #brand produs [8]
                        price_per_unit = item.find(attrs={"data-testid": "product-block-price-per-unit"}).text.strip() #pret per kg/l/buc produs
                        for id_category in range(0,len(price_per_unit)):
                            if price_per_unit[id_category] == ',':
                                price_per_unit = price_per_unit.replace(',','.')
                                break
                        print(f"PRICE_PER_UNIT: {price_per_unit}")
                        unit = price_per_unit[price_per_unit.find('/')+1:] #aflare daca e kg sau litru
                        price_per_unit = price_per_unit[:price_per_unit.find(' ')]
                        price = price_format(item.find(attrs={"data-testid": "product-block-price"})) #pret produs
                        offer = "0" #reducere produs (CONNECT, flat % sau reducere la cumpararea a mai multor produse de acelasi fel)
                        old_price = "" #in caz de promotie, pretul produsului fara reducere
                        old_ppu = "" #in caz de promotie, pretul produsului per kg/l fara reducere
                        image = item.find(attrs={"data-testid": "product-block-image"})
                        if image:
                            image_src = image['src']
                        if item.find(attrs={"data-testid":"tag-offer"}): #daca exista butonul cu id de promotie, se insereaza valoarea promotiei si se cauta si pretul vechi
                            offer = item.find(attrs={"data-testid":"tag-offer"}).text.strip()
                            if item.find(attrs={"data-testid": "product-block-old-price"}):
                                old_price = price_format(item.find(attrs={"data-testid": "product-block-old-price"})) # se formateaza cu price_format
                            if item.find(attrs={"data-testid": "product-block-old-ppu"}):
                                old_ppu = (item.find(attrs={"data-testid": "product-block-old-ppu"})).text.strip()
                            '''
                        print(f"{brand} {name}: \n {price} ({price_per_unit})")
                        '''
                        if offer !="0":
                            print(offer)
                            print(old_price, old_ppu)
                        print(image_src)
                        '''
                        cursor = connection.cursor()
                        sql = "INSERT INTO product (product_name, product_description, ingredients, product_brand, price, image_src, price_per_unit, unit, offer, id_category) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s)"
                        val = (name, description, ingredients, brand, price, image_src, price_per_unit, unit, offer, id_category)
                        cursor.execute(sql, val)
                        connection.commit()
                        print(f"Record inserted. ID: {cursor.lastrowid}")
                        cursor.close()
                        
                    print("\n")
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
    

