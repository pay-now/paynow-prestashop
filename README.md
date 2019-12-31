[**English version**][ext0]
# Wtyczka Paynow dla PrestaShop 

Wtyczka Paynow dodaje szybkie płatności i płatności BLIK do sklepu PrestaShop.

Wtyczka wspiera PrestaShop w wersji 1.6.0 lub wyższej.

## Spis treści
* [Instalacja](#instalacja)
* [Konfiguracja](#konfiguracja)
* [FAQ](#FAQ)
* [Sandbox](#sandbox)
* [Wsparcie](#wsparcie)
* [Licencja](#licencja)

## Instalacja
1. Pobierz wtyczkę z [repozytorium Github][ext1] i zapisz plik .zip na dysku swojego komputera
2. Rozpakuj pobrane archiwum
3. Zmień nazwę rozpakowanego folderu na `paynow` 
4. Z folderu `paynow` utwórz archiwum paynow.zip
5. Przejdź do panelu administracyjnego PrestaShop
6. Przejdź do zakładki `Moduły > Module Manager`

![Instalacja krok 6][ext3]

7. Wybierz opcję `Załaduj moduł` i wskaż archiwum zawierające wtyczkę (utworzone w kroku 3)

![Instalacja krok 7][ext4]

8. Załaduj wtyczkę

## Konfiguracja
1. Przejdź do panelu administracyjnego PrestaShop
2. Przejdź do zakładki `Moduły > Module Manager`
3. Wyszukaj `Paynow` i wybierz opcję `Konfiguruj`

![Konfiguracja krok 3][ext5]

4. Klucze dostępu znajdziesz w `Ustawienia > Sklepy i punkty płatności > Dane uwierzytelniające` w panelu sprzedawcy Paynow

![Konfiguracja krok 4][ext6]

5. W zależności od środowiska, z którym chesz się połaczyć w sekcji `Konfiguracja środowiska produkcyjnego` lub `Konfiguracja środowiska testowego` wpisz `Klucz API` i `Klucz podpisu` 

![Konfiguracja krok 5][ext7]

## Sandbox
W celu przetestowania działania bramki Paynow zapraszamy do skorzystania z naszego środowiska testowego. W tym celu zarejestruj się na stronie: [panel.sandbox.paynow.pl][ext2]. 

## Wsparcie
Jeśli masz jakiekolwiek pytania lub problemy, skontaktuj się z naszym wsparciem technicznym: support@paynow.pl.

Jeśli chciałbyś dowiedzieć się więcej o bramce płatności Paynow odwiedź naszą stronę: https://www.paynow.pl/.

## Licencja
Licencja MIT. Szczegółowe informacje znajdziesz w pliku LICENSE.

[ext0]: README.EN.md
[ext1]: https://github.com/pay-now/paynow-prestashop/releases/latest
[ext2]: https://panel.sandbox.paynow.pl/auth/register
[ext3]: instruction/step1.png
[ext4]: instruction/step2.png
[ext5]: instruction/step3.png
[ext6]: instruction/step4.png
[ext7]: instruction/step5.png