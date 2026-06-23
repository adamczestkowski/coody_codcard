# coody_codcard — Płatność kartą przy odbiorze i przelewem

**Wersja:** 1.3.1  
**Autor:** [Coody](https://github.com/adamczestkowski/coody_codcard)  
**PrestaShop:** 1.7+ (testowane na 9.x)

Moduł płatności offline z **dwoma metodami** w jednej instancji:

1. **Płatność kartą przy odbiorze** — klient płaci kartą u kuriera / w punkcie odbioru.
2. **Płatność przelewem** — jak `ps_wirepayment`, ale z **osobnym numerem konta dla każdej waluty** sklepu.

---

## Funkcje

### Checkout — dwie opcje płatności

| Metoda | Kiedy widoczna | Co pokazuje |
|--------|----------------|-------------|
| Karta przy odbiorze | Koszyk z dostawą (nie-wirtualny) | Treść informacyjna o płatności kartą (edytowalna w BO) |
| Przelew | Gdy konto dla bieżącej waluty jest skonfigurowane | Tekst informacyjny o przelewie + numer konta + kwota |

Przy przelewie wyświetlany jest domyślny tekst informacyjny (wielojęzyczny, wbudowany w moduł) — np. *„Prosimy o przelew na kwotę zamówienia na konto wskazane poniżej…”*.

Obie metody działają we **wszystkich walutach** z jednej instancji modułu.

### Potwierdzenie zamówienia

- Osobny szablon dla karty i dla przelewu (`displayOrderConfirmation`)
- Przy przelewie: kwota, numer zamówienia, konto dla waluty zamówienia
- Link do formularza kontaktowego (działający od 1.3.1)

### Maile (przelew)

Przy płatności przelewem klient dostaje **dwa maile**:

1. **`order_conf`** — standardowe potwierdzenie zamówienia ze sklepu (produkty, adresy, suma). Moduł **nie modyfikuje** tego szablonu.
2. **`coody_codcard_wire`** — osobny mail z modułu: logo sklepu, kwota, numer konta, tytuł przelewu, tytuł wiadomości w języku zamówienia.

Szablony maila modułu są w `modules/coody_codcard/mails/` (PL + EN, z fallbackiem na EN) — **nie trzeba edytować maili sklepu**.

### Zmienne mailowe (opcjonalnie)

Hooki `sendMailAlterTemplateVars` i `actionGetExtraMailTemplateVars` wypełniają zmienne tylko przy zamówieniach przelewem z tego modułu. Działają **wyłącznie**, gdy ręcznie dodasz placeholdery do szablonu sklepu:

| Zmienna | Opis |
|---------|------|
| `{coody_codcard_wire_payment_html}` | Blok HTML z kwotą, kontem i tytułem przelewu |
| `{coody_codcard_wire_payment_txt}` | Wersja tekstowa tego samego bloku |

Bez placeholderów w `order_conf` zmienne są ignorowane — dane przelewu idą osobnym mailem modułu.

### Back office

- Treść przy opcji **karta przy odbiorze** (wielojęzyczna, edytowalna)
- **Numery kont wg walut** — osobne pole na walutę (IBAN, bank, właściciel)
- Stany zamówienia: *Oczekiwanie na płatność kartą przy odbiorze* / *Oczekiwanie na przelew*

---

## Instalacja na nowym sklepie

1. Skopiuj folder `modules/coody_codcard` (opcjonalnie bez `.git`).
2. BO → **Moduły** → **Zainstaluj** moduł (przy aktualizacji: **Aktualizuj**).
3. **Konfiguruj** → uzupełnij numery kont per waluta (+ opcjonalnie treść przy karcie przy odbiorze).
4. Sprawdź **Płatności → Preferencje** — moduł powinien być włączony dla walut (ustawiane przy instalacji).
5. Zalecane: **wyłącz `ps_wirepayment`**, żeby nie dublować przelewu.
6. Wyczyść cache po zmianach.
7. Złóż testowe zamówienie przelewem i sprawdź oba maile w skrzynce / Mailpicie.

---

## Konfiguracja przelewu (multi-waluta)

Sekcja **„Płatność przelewem — numery kont wg walut”** w konfiguracji modułu — jedno pole na każdą walutę sklepu. Przykład:

```
PL12 3456 7890 1234 5678 9012 3456
Bank Example S.A.
```

Opcja przelewu w checkout **pojawi się tylko**, gdy konto dla aktualnej waluty koszyka jest uzupełnione.

**Klucze konfiguracji:**
- `COODY_CODCARD_ACCOUNTS` — JSON kont per waluta
- `COODY_OS_WIRE_VALIDATION` — ID stanu zamówienia (przelew)

---

## Opcjonalnie: jeden mail zamiast dwóch

Jeśli wolisz **jeden** mail z danymi przelewu w `order_conf`, edytuj szablony sklepu (`mails/pl/order_conf.html` i `.txt`) i dodaj pod linią z `{payment}`:

```html
{coody_codcard_wire_payment_html}
```

```
{coody_codcard_wire_payment_txt}
```

Moduł sam nie zmienia szablonów sklepu — placeholdery trzeba dodać ręcznie. Bez tego dane przelewu trafiają wyłącznie w mailu `coody_codcard_wire`.

---

## Repozytorium

https://github.com/adamczestkowski/coody_codcard
