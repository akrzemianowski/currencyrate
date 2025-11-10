# Instalacja

## Sklonuj repozytorium PrestaShop
```
git clone git@github.com:akrzemianowski/PrestaShop.git
```

## Przełącz na branch z modułem currencyrate
```
git checkout currency-rate
```

## Uruchom środowisko
```
make docker-start
```
## Przy pierwszym uruchomieniu instalują się zależności composera, instaluje się baza danych i FO i BO PrestaShop będą dostępne z opóźnieniem

## Adresy środowiska:
``` 
# FO: http://localhost:8001
# BO: http://localhost:8001/admin-dev
```

## Credentiale Administratora
```
# Email: demo@prestashop.com
# Password: Correct Horse Battery Staple

```

## Skonfiguruj sklep
```
Zalogouj się do BO używając powyższych credentiali. Zaimportuj pakiet dla języka polskiego. Ustaw PLN jako domyślną walutę.
```

# Moduł CurrencyRate

1. Automatyczne pobieranie kursów walut Synchronizuje kursy z dwóch źródeł: NBP (dla PLN) i Frankfurter/ECB (dla EUR) przez API 
2. Dynamiczna lista walut Automatycznie pobiera kursy dla wszystkich aktywnych walut skonfigurowanych w PrestaShop 
3. Wyświetlanie cen w wielu walutach Na stronie produktu pokazuje tabelę z cenami przeliczonymi na wszystkie aktywne waluty z aktualnymi kursami 
4. Komenda konsolowa php bin/console prestashop:currency-rate:refresh - możliwość ustawienia okresu (dni) i waluty bazowej - możliwość podłączenia do crona
5. Panel administracyjny Konfiguracja providera, automatycznej aktualizacji, przycisk ręcznego pobrania danych, statystyki 
6. Historia kursów - strona publiczna Dostępna pod /module/currencyrate/history z paginacją, sortowaniem i filtrowaniem (ostatnie 30 dni) 
7. Przechowywanie w bazie danych Tabela ps_currency_rate z indeksami dla szybkiego dostępu, unikalne rekordy (waluta+data+provider) 
8. System cache Dwupoziomowy cache (ceny produktów + kursy walut) ważny 1 godzinę, automatyczne czyszczenie po synchronizacji 