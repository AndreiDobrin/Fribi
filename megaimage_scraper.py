from selenium import webdriver
from bs4 import BeautifulSoup
import time

import mysql.connector
from mysql.connector import Error
try:
    # 1. Establish the connection
    connection = mysql.connector.connect(
        host='localhost',
        port='3307',                 # Default XAMPP host
        database='andrei',        # Your database name
        user='root',               # Default XAMPP user
        password=''                # Default XAMPP password is empty
    )

    if connection.is_connected():
        db_info = connection.get_server_info()
        print(f"Connected to MySQL Server version {db_info}")
        
        cursor = connection.cursor()
        cursor.execute("select database();")
        record = cursor.fetchone()
        print(f"You're connected to database: {record}")


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


        # selenium
        driver = webdriver.Chrome()
        driver.get("https://www.mega-image.ro/Fructe-si-legume-proaspete/c/001")

        # javascript load time
        time.sleep(2) 


        # infinite scroll
        last_height = driver.execute_script("return document.body.scrollHeight")
        '''
        while True:
            driver.execute_script("window.scrollTo(0, document.body.scrollHeight);")
            time.sleep(2)
            new_height = driver.execute_script("return document.body.scrollHeight")

            if new_height == last_height:
                print("Reached the end of the page...")
                break

            last_height = new_height
            #print(f"Scrolled to: {new_height}")
        '''
        # salveaza codul html
        html = driver.page_source

        # inchide selenium
        driver.quit()

        # parser
        soup = BeautifulSoup(html, 'html.parser')

        items = soup.find_all(attrs={"data-testid": "product-block"})

        for item in items:

            name = item.find(attrs={"data-testid": "product-name"}).text.strip() #nume produs
            brand = item.find(attrs={"data-testid": "product-brand"}).text.strip() #brand produs
            
            #verificare daca produs deja exista
            if (name,brand) in products:
                print(f"Articolul {brand} {name} deja exista...") # DE VERIFICAT DACA DETALIILE PRODUSULUI S-AU SCHIMBAT
            else:
                price_per_unit = item.find(attrs={"data-testid": "product-block-price-per-unit"}).text.strip() #pret per kg/l produs
                unit = price_per_unit[price_per_unit.find('/')+1:] #aflare daca e kg sau litru
                price = item.find(attrs={"data-testid": "product-block-price"}) #pret produs
                promo = "0" #reducere produs (CONNECT, flat % sau reducere la cumpararea a mai multor produse de acelasi fel)
                old_price = "" #in caz de promotie, pretul produsului fara reducere
                old_ppu = "" #in caz de promotie, pretul produsului per kg/l fara reducere
                image = item.find(attrs={"data-testid": "product-block-image"})
                image_src = image['src']

                if item.find(attrs={"data-testid":"tag-promo"}): #daca exista butonul cu id de promotie, se insereaza valoarea promotiei si se cauta si pretul vechi
                    promo = item.find(attrs={"data-testid":"tag-promo"}).text.strip()
                    if item.find(attrs={"data-testid": "product-block-old-price"}):
                        old_price = price_format(item.find(attrs={"data-testid": "product-block-old-price"})) # se formateaza cu price_format
                    if item.find(attrs={"data-testid": "product-block-old-ppu"}):
                        old_ppu = (item.find(attrs={"data-testid": "product-block-old-ppu"})).text.strip()

                price = price_format(price) # formatare pret

                print(f"{brand} {name}: \n {price} ({price_per_unit})")
                if promo !="0":
                    print(promo)
                    print(old_price, old_ppu)
                print(image_src)
                #DE VAZUT CATEGORIA
                cursor = connection.cursor()
                sql = "INSERT INTO product (product_name, product_brand, price, image_src, price_per_unit, unit, offer, id_category) VALUES (%s, %s, %s, %s, %s, %s, %s, %s)"
                val = (name, brand, price, image_src, price_per_unit, unit, promo, 1) #DE VAZUT CATEGORIA
                cursor.execute(sql, val)
                connection.commit()
                print(f"Record inserted. ID: {cursor.lastrowid}")
                cursor.close()
                
            print("\n\n")


        print(len(items))


except Error as e:
    print(f"Error while connecting to MySQL: {e}")

finally:
    # 2. Always close the connection
    if connection.is_connected():
        cursor.close()
        connection.close()
        print("MySQL connection is closed")





