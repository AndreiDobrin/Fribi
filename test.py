'''
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
        
        def read_products(connection):
            cursor = connection.cursor()
            cursor.execute("SELECT product_name,product_brand FROM product")
            result = cursor.fetchall()
            if result:
                for row in result:
                    print(row)
            else:
                print("Nu au fost gasite rezultate")
            cursor.close()
            return result
        
        def insert_product(connection, products):
            if ('test',) in products:
                print("Valoare deja in DB")
                return
            else:
                print(products)
            cursor = connection.cursor()
            #sql = "INSERT INTO product (id_category, price, product_name) VALUES (%s, %s, %s)"
            val = (1, 10.01, "test")
            #cursor.execute(sql, val)
            #connection.commit()
            print(f"Record inserted. ID: {cursor.lastrowid}")
            cursor.close()
        
        
        products = read_products(connection)
        insert_product(connection, products)

except Error as e:
    print(f"Error while connecting to MySQL: {e}")
    
    
    


finally:
    # 2. Always close the connection
    if connection.is_connected():
        cursor.close()
        connection.close()
        print("MySQL connection is closed")

        
        
'''

