[**English version**][ext0]

# Wtyczka do PrestaShop integrująca bramkę paynow

Wtyczka `paynow` dodaje bezpieczne płatności BLIK, szybkie przelewy internetowe oraz płatności kartą.

## Spis treści

- [Wymagania](#wymagania)
- [Instalacja](#instalacja)
- [Konfiguracja](#konfiguracja)
- [FAQ](#FAQ)
- [Sandbox](#sandbox)
- [Wsparcie](#wsparcie)
- [Licencja](#licencja)

## Wymgania

- PHP od wersji 7.2
- PrestaShop od wersji 1.6.0

## Instalacja

Zobacz również [filmik instruktażowy][ext8].

1. Pobierz plik [paynow.zip][ext1] i zapisz na dysku swojego komputera
2. Przejdź do panelu administracyjnego PrestaShop
3. Przejdź do zakładki `Moduły > Module Manager`

![Instalacja krok 6][ext3]

4. Wybierz opcję `Załaduj moduł` i wskaż archiwum zawierające wtyczkę (pobrane w kroku 1)

![Instalacja krok 7][ext4]

5. Załaduj wtyczkę

## Konfiguracja

1. Przejdź do panelu administracyjnego PrestaShop
2. Przejdź do zakładki `Moduły > Module Manager`
3. Wyszukaj `paynow` i wybierz opcję `Konfiguruj`

![Konfiguracja krok 3][ext5]

4. Produkcyjne klucze dostępu znajdziesz w zakładce `Mój biznes > Paynow > Ustawienia > Sklepy i punkty płatności > Dane uwierzytelniające` w bankowości internetowej mBanku.

   Klucze dla środowiska testowego znajdziesz w zakładce `Ustawienia > Sklepy i punkty płatności > Dane uwierzytelniające` w [panelu środowiska testowego][ext10].

![Konfiguracja krok 4a][ext6]

![Konfiguracja krok 4b][ext11]

5. W zależności od środowiska, z którym chcesz się połączyć w sekcji `Konfiguracja środowiska produkcyjnego` lub `Konfiguracja środowiska testowego` wpisz `Klucz API` i `Klucz podpisu`

![Konfiguracja krok 5][ext7]

## FAQ

**Jak skonfigurować adres powrotu?**

Adres powrotu ustawi się automatycznie dla każdego zamówienia. Nie ma potrzeby ręcznej konfiguracji tego adresu.

**Jak skonfigurować adres powiadomień?**

W panelu sprzedawcy paynow przejdź do zakładki `Ustawienia > Sklepy i punkty płatności`, w polu `Adres powiadomień` ustaw adres:
`https://twoja-domena.pl/module/paynow/notifications`.

![Konfiguracja adresu powiadomień][ext9]

## Sandbox

W celu przetestowania działania bramki paynow zapraszamy do skorzystania z naszego środowiska testowego. W tym celu zarejestruj się na stronie: [panel.sandbox.paynow.pl][ext2].

## Wsparcie

Jeśli masz jakiekolwiek pytania lub problemy, skontaktuj się z naszym wsparciem technicznym: support@paynow.pl.

Jeśli chciałbyś dowiedzieć się więcej o bramce płatności paynow odwiedź naszą stronę: https://www.paynow.pl/.

## Licencja

Licencja MIT. Szczegółowe informacje znajdziesz w pliku LICENSE.

[ext0]: README.EN.md
[ext1]: https://github.com/pay-now/paynow-prestashop/releases/latest/download/paynow.zip
[ext2]: https://panel.sandbox.paynow.pl/auth/register
[ext3]: instruction/step1.png
[ext4]: instruction/step2.png
[ext5]: instruction/step3.png
[ext6]: instruction/step4a.png
[ext7]: instruction/step5.png
[ext8]: https://paynow.wistia.com/medias/nym9wdwdwl
[ext9]: instruction/step6.png
[ext10]: https://panel.sandbox.paynow.pl/merchant/payments
[ext11]: instruction/step4b.png
