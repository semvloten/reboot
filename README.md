# Reboot

Reboot is een marktplaats voor gebruikte laptops, consoles en telefoons. Klanten melden apparaten aan en volgen de keuring. Keurmeesters controleren de apparaten en leggen hun bevindingen vast. Alleen goedgekeurde apparaten komen in de winkel, waar andere klanten ze kunnen reserveren.

De betaalpagina is een demo: er wordt geen geld afgeschreven.

## Projectgegevens

| Onderdeel | Gegevens |
| --- | --- |
| Opdracht | Project 9 - Refurbished Tech Marketplace |
| Projectnaam | Reboot |
| Opdrachtgever | Elektronicawinkel Circuit Renew |
| Contactpersoon | Mevr. M. Smit, e-commerce manager |
| Datum projectbriefing | 25 augustus 2026 |
| Complexiteit | Medium |
| Techniek | Laravel 12.69.2, React 19, Inertia 2 en Tailwind CSS 4 |
| Database | MySQL/MariaDB |

## Installatie

### 1. Benodigde software

Installeer:

- [XAMPP](https://www.apachefriends.org/download.html) met **PHP 8.2** of hoger en MySQL/MariaDB.
- [Composer 2](https://getcomposer.org/doc/00-intro.md#installation-windows). Selecteer bij de installatie bijvoorbeeld `C:\xampp\php\php.exe`.
- [Node.js 22 of 24 LTS](https://nodejs.org/en/download), inclusief npm.

Zorg dat `C:\xampp\php` in de Windows-omgevingsvariabele **Path** staat en open daarna een nieuwe PowerShell-terminal. Controleer:

```powershell
php --version
composer --version
node --version
npm --version
```

Schakel de extensies `pdo_mysql` en `zip` in de gebruikte `php.ini` in als ze nog uitstaan. De locatie vind je met `php --ini`.

### 2. Project uitpakken

Pak het meegeleverde ZIP-bestand met de code en volledige database-export uit. Open in PowerShell de map waarin `artisan`, `composer.json` en `package.json` staan, bijvoorbeeld:

```powershell
cd C:\xampp\htdocs\Reboot\reboot
```

Voor de ZIP-installatie zijn **Git en een SSH-key niet nodig**. Klonen via Git/SSH kan ook; gebruik dan de meegeleverde repositorylink en volg vanaf stap 3 dezelfde installatie.

### 3. Configuratie instellen

Kopieer bij een eerste installatie het voorbeeldbestand:

```powershell
Copy-Item .env.example .env
```

Bestaat `.env` al? Bewerk dat bestand zonder het te overschrijven. De instellingen worden overgenomen uit de meegeleverde `.env.example`. Controleer vooral de databasegegevens:

```dotenv
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=reboot
DB_USERNAME=root
DB_PASSWORD=
```

Dit zijn de waarden uit `.env.example`. Pas gebruikersnaam, wachtwoord en poort alleen aan als jouw lokale database andere instellingen heeft.

Zet voor Windows in `.env` `PHP_CLI_SERVER_WORKERS=1` en voeg `INERTIA_SSR_ENABLED=false` toe: deze lokale installatie gebruikt geen SSR-server. In stap 4 wordt een nieuwe `APP_KEY` gegenereerd; je hoeft de sleutel uit het voorbeeld niet handmatig over te nemen.

### 4. Afhankelijkheden installeren

```powershell
composer install --prefer-dist
composer check-platform-reqs
npm ci
php artisan key:generate --no-interaction
```

Laravel wordt automatisch geïnstalleerd via Composer; je hoeft geen nieuw Laravel-project aan te maken. Als PowerShell `npm.ps1` blokkeert, gebruik dan `npm.cmd` in plaats van `npm`.

### 5. Database importeren

1. Start **Apache** en **MySQL** via het XAMPP Control Panel.
2. Open [phpMyAdmin](http://localhost/phpmyadmin).
3. Maak een lege database `reboot` met collatie `utf8mb4_unicode_ci`.
4. Selecteer die database en kies **Importeren**.
5. Importeer het volledige meegeleverde `.sql`-bestand uit de ZIP.

De export bevat de bestaande tabellen, accounts, apparaten en keuringen. Als de export een andere databasenaam gebruikt, pas `DB_DATABASE` in `.env` daarop aan.

Controleer daarna:

```powershell
php artisan config:clear
php artisan migrate:status --no-interaction
```

Alleen als er migraties met de status **Pending** zijn, voer je uit:

```powershell
php artisan migrate --no-interaction
```

Er is geen seeder nodig. Gebruik geen `migrate:fresh`: dit verwijdert de geïmporteerde gegevens.

### 6. Foto's controleren

De meegeleverde apparaatfoto's moeten met hun bestaande submappen in `storage/app/private/devices` staan. Foto's zitten niet in de SQL-export. Als ze na uitpakken al op die plek staan, hoef je niets te doen.

### 7. App starten

```powershell
npm run build
php artisan serve --host=localhost --port=8000
```

Open **[http://localhost:8000](http://localhost:8000)** en log in met de meegeleverde demo-inloggegevens. Houd MySQL en de Artisan-terminal geopend. Met **Ctrl+C** stop je de app.

Een klant kan apparaten aanmelden en apparaten van andere klanten reserveren. Een keurmeester kan via **Apparaten keuren** aanmeldingen prioriteren, toewijzen en beoordelen.
