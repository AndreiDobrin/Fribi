from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from bs4 import BeautifulSoup
import time
import os

import mysql.connector
from mysql.connector import Error

if 'DYNO' in os.environ:
    # We are on Heroku, use Config Vars
    db_host = os.environ.get('DB_HOST')
    db_user = os.environ.get('DB_USER')
    db_pass = os.environ.get('DB_PASSWORD')
    db_name = os.environ.get('DB_NAME')
    db_port = os.environ.get('DB_PORT')
else:
    # We are on Local machine
    db_host = 'localhost'
    db_user = 'root'
    db_pass = ''
    db_name = 'andrei'
    db_port = 3307



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
                alphabet = [i for i in range(ord('A'), ord('Z')+1)]
                string = string.split()

                copy = []
                for i in range(0,len(string)):
                    if ord(string[i][0]) not in alphabet:
                        copy.append(string[i])
                price = '.'.join(copy)
                return price


            chrome_options = Options()
            chrome_options.add_argument("--headless") # Runs without a visible UI
            chrome_options.add_argument("--disable-dev-shm-usage") # Overcome limited resource problems
            chrome_options.add_argument("--no-sandbox") # Bypass OS security model

            # selenium
            driver = webdriver.Chrome(options=chrome_options)
            driver.get("https://www.mega-image.ro/Fructe-si-legume-proaspete/c/001")

            # javascript load time
            time.sleep(2) 


            # infinite scroll

            last_height = driver.execute_script("return document.body.scrollHeight")
            while True:
                driver.execute_script("window.scrollTo(0, document.body.scrollHeight-2000);")
                time.sleep(5)
                new_height = driver.execute_script("return document.body.scrollHeight")

                if new_height == last_height:
                    print("Reached the end of the page...")
                    break

                last_height = new_height
                print(f"Scrolled to: {new_height}")


            # salveaza codul html
            html = driver.page_source

            # inchide selenium
            driver.quit()

            # parser
            soup = BeautifulSoup(html, 'html.parser')

            items = soup.find_all(attrs={"data-testid": "product-block"})
            for item in items:
                #verificare daca produs deja exista
                ok = 1
                for product in products:
                    name = item.find(attrs={"data-testid": "product-name"}).text.strip() #nume produs [4]
                    brand = item.find(attrs={"data-testid": "product-brand"}).text.strip() #brand produs [8]
                    if product[4] == name and product[8] == brand: # produsul a fost gasit. se verifica daca detaliile s-au schimbat
                        print(f"produsul a fost gasit")
                        '''
                        price = price_format(item.find(attrs={"data-testid": "product-block-price"}))
                        if price != product[2]:
                            ok = 2
                            break
                        '''
                        price = price = price_format(item.find(attrs={"data-testid": "product-block-price"})) #pret produs [2]

                        if item.find(attrs={"data-testid":"tag-promo"}): #daca exista butonul cu id de promotie, se insereaza valoarea promotiei si se cauta si pretul vechi
                            promo = item.find(attrs={"data-testid":"tag-promo"}).text.strip() # [3]
                        else:
                            promo = 0 #reducere produs (CONNECT, flat % sau reducere la cumpararea a mai multor produse de acelasi fel)

                        product_description = None # [5]
                        product_ingredients = None # [6]

                        image = item.find(attrs={"data-testid": "product-block-image"})
                        if image:
                            image_src = image['src']
                        else:
                            image_src = None # [7]

                        price_per_unit = item.find(attrs={"data-testid": "product-block-price-per-unit"}).text.strip() #pret per kg/l/buc produs
                        for i in range(0,len(price_per_unit)):
                            if price_per_unit[i] == ',':
                                price_per_unit = price_per_unit.replace(',','.') # [9]
                                break
                        unit = price_per_unit[price_per_unit.find('/')+1:] #aflare daca e kg sau litru [10]
                        price_per_unit = price_per_unit[:price_per_unit.find(' ')]
                        '''
                        old_price = "" #in caz de promotie, pretul produsului fara reducere
                        old_ppu = "" #in caz de promotie, pretul produsului per kg/l fara reducere
                        '''
                        item_in_list_form = ["id","id_category", price, promo , name, product_description, product_ingredients, image_src, brand, price_per_unit, unit]
                        print(item_in_list_form[2:])

                        if float(product[2]) != float(price) or product[3] != promo or product[4] != name or product[5] != product_description or product[6] != product_ingredients or product[7] != image_src or product[8] != brand or float(product[9]) != float(price_per_unit) or product[10] != unit:
                            cursor = connection.cursor()
                            sql = "UPDATE product SET price = %s, offer = %s, product_name = %s, product_description = %s, ingredients = %s, image_src = %s, product_brand = %s, price_per_unit = %s, unit = %s WHERE product_name = %s AND product_brand = %s"
                            val = (price, promo, name, product_description, product_ingredients, image_src, brand, price_per_unit, unit, name, brand)
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
                    name = item.find(attrs={"data-testid": "product-name"}).text.strip() #nume produs [4]
                    brand = item.find(attrs={"data-testid": "product-brand"}).text.strip() #brand produs [8]
                    price_per_unit = item.find(attrs={"data-testid": "product-block-price-per-unit"}).text.strip() #pret per kg/l/buc produs
                    for i in range(0,len(price_per_unit)):
                        if price_per_unit[i] == ',':
                            price_per_unit = price_per_unit.replace(',','.')
                            break
                    print(f"PRICE_PER_UNIT: {price_per_unit}")
                    unit = price_per_unit[price_per_unit.find('/')+1:] #aflare daca e kg sau litru
                    price_per_unit = price_per_unit[:price_per_unit.find(' ')]
                    price = price_format(item.find(attrs={"data-testid": "product-block-price"})) #pret produs
                    promo = "0" #reducere produs (CONNECT, flat % sau reducere la cumpararea a mai multor produse de acelasi fel)
                    old_price = "" #in caz de promotie, pretul produsului fara reducere
                    old_ppu = "" #in caz de promotie, pretul produsului per kg/l fara reducere
                    image = item.find(attrs={"data-testid": "product-block-image"})
                    if image:
                        image_src = image['src']

                    if item.find(attrs={"data-testid":"tag-promo"}): #daca exista butonul cu id de promotie, se insereaza valoarea promotiei si se cauta si pretul vechi
                        promo = item.find(attrs={"data-testid":"tag-promo"}).text.strip()
                        '''
                        if item.find(attrs={"data-testid": "product-block-old-price"}):
                            old_price = price_format(item.find(attrs={"data-testid": "product-block-old-price"})) # se formateaza cu price_format
                        if item.find(attrs={"data-testid": "product-block-old-ppu"}):
                            old_ppu = (item.find(attrs={"data-testid": "product-block-old-ppu"})).text.strip()
                        '''



                    print(f"{brand} {name}: \n {price} ({price_per_unit})")
                    if promo !="0":
                        print(promo)
                        print(old_price, old_ppu)
                    print(image_src)
                    cursor = connection.cursor()
                    sql = "INSERT INTO product (product_name, product_brand, price, image_src, price_per_unit, unit, offer, id_category) VALUES (%s, %s, %s, %s, %s, %s, %s, %s)"
                    val = (name, brand, price, image_src, price_per_unit, unit, promo, 1) #DE VAZUT CATEGORIA
                    cursor.execute(sql, val)
                    connection.commit()
                    print(f"Record inserted. ID: {cursor.lastrowid}")
                    cursor.close()

                print("\n")


            print(len(items))


    except Error as e:
        print(f"Error while connecting to MySQL: {e}")

    finally:
        # 2. Always close the connection
        if connection.is_connected():
            cursor.close()
            connection.close()
            print("MySQL connection is closed")

    time.sleep(3600*24)



