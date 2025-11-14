# Fribi

## Prezentare Temă

Această aplicație are rolul de a centraliza produsele alimentare puse la vânzare de mai
multe magazine online/hypermarket-uri, automat. Scopul principal este de a promova
frugalitatea, astfel ținând cont de promoții curente și totodată prețuri pe care produsele
le-au avut în trecut.

## Funcționalități principale

Utilizatorul poate rămâne anonim (caz în care dacă pune produse în coș, informația se
va păstra în cookies și dacă se înregistrează cu produse în coș, acestea se vor
transfera in coșul utilizatorului nou înregistrat) sau poate să se înregistreze, căpătând
acces la opțiunea de abonare la newsletter pentru oferte la produse care îl interesează.
Se va implementa și funcția de a iniția o comandă online, chiar dacă acesta nu este
unul din scopurile aplicației. Astfel se va genera o factura care va fi trimisă prin e-mail
sau care se va descărca prin website.

- PHP – conectarea frontend-ului cu baza de date
- MariaDB – baza de date care va stoca informațiile utilizatorilor și informații
despre produse;
- Python – web scraper-ele care vor lua informațiile despre produsele
magazinelor selectate si le vor stoca în baza de date MariaDB
- Redis – tehnologie pentru funcționalitatea newsletter-ului

## Web scraper

Din moment ce magazinele de pe care vor fi preluate informațiile necesare sunt diferite,
va fi nevoie de un scraper personalizat pentru fiecare website. Totodată, aceste
scraper-e vor trebui sa fie programate astfel încât sa ruleze la un anumit interval de timp
pentru a actualiza conform informațiile despre produse

## Newsletter

Acesta reprezintă capabilitatea de a trimite e-mail utilizatorilor abonați atunci când
apare o reducere la unul sau mai multe produse. Am ales Redis ca și tehnologie pentru
implementarea acestei funcționalități, pentru simplicitate.

## Generare facturi 

După ce clientul va plasa o comandă, acesta va primi factura pe e-mail (sau se va
genera si descărca sub format PDF prin browser), factura fiind standardizată (având un
template). Aceasta va conține datele clientului, produsele cumpărate, suma totală plătită
și data comenzii.

## Baza de date

![php_database](https://github.com/user-attachments/assets/6d8074f4-5d78-4366-b289-3edd8371d8e4)

USER – va avea coloana privilege, pe lângă detaliile personale uzuale, care va
determina daca utilizatorul respectiv este un utilizator normal sau privilegiat, care poate
schimba valorile unui produs;

COMPANY – reprezintă magazinul din care face parte un produs (nu marca). De
exemplu: Auchan, Freshful, Carrefour, Mega Image, etc. . Acest tabel va fi folositor
pentru a vedea la ce magazine se regăsesc produsele și in backend cât și în frontend.
Produsele pe care le are o companie respectivă se regăsesc în tabelul intermediar
COMPANY_PRODUCTS;

CART – coșul de cumpărături al utilizatorului. Articolele efective se vor pune în tabelul
intermediar CART_PRODUCTS;


PRODUCT – promoțiile curente se vor stoca în coloana “offer”. Va trebui implementat și
un historical low sau o altă metodă pentru a vedea ce prețuri a mai avut in trecut.
Aceste produse fac parte dintr-o categorie după care se poate face căutarea (tabelul
CATEGORY);

SHOPPING_ORDER – Aici se regăsesc comenzile utilizatorilor, unde fiecare comandă
are un status predefinit, default începând cu “Pending”. Totodată, se regăsește și data
plasării comenzii și suma totală. Produsele efective se regăsesc în tabelul intermediar
ORDER_PRODUCTS, care are coloana “price_at_purchase”, pentru a nu procesa
mereu datele produsului și pentru că prețul produsului se poate schimba;

REVIEW – Se pot lăsa recenzii produselor (utilizatorul nu poate lăsa mai multe recenzii
aceluiași produs, dar își poate actualiza recenzia), oferind un scor de la 1 la 5 și,
opțional, să fie lăsat un comentariu. Se stochează data la care a fost creat cu
“current_timestamp” și daca va fi actualizat se va stoca și acea dată;

NEWSLETTER – Utilizatorii se pot abona la un newsletter pentru un produs sau o
categorie de produse (trebuie să fie logați). Utilizatorul care este abonat la newsletter-ul
unui produs va primi un e-mail atunci când produsul face parte dintr-o promoție;

