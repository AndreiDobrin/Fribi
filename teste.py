from selenium import webdriver
from bs4 import BeautifulSoup
import time

import mysql.connector
from mysql.connector import Error
try:
    # 1. Establish the connection
    connection = mysql.connector.connect(
        host='localhost',
        port='3307',
        database='andrei',
        user='root',
        password=''
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
        
        if float(products[19][9]) == 135.92:
            print(products[19][9])

        
except Error as e:
    print(f"Error while connecting to MySQL: {e}")

finally:
    # 2. Always close the connection
    if connection.is_connected():
        cursor.close()
        connection.close()
        print("MySQL connection is closed")

