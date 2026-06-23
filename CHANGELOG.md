# Changelog — coody_codcard

Wszystkie istotne zmiany w module są dokumentowane w tym pliku.

---

## [1.3.1] — 2025-06-23

### Dodano

- Domyślny tekst informacyjny przy opcji **przelew** w checkout (`defaultWireIntroContent`) — wielojęzyczny, wbudowany w moduł.
- Logo sklepu (`{shop_logo}`) w mailu `coody_codcard_wire`.
- Tytuły maila przelewu per język zamówienia (np. PL: *Dane do przelewu — zamówienie …*).

### Zmieniono

- Wersja modułu: `1.3.0` → `1.3.1`.
- Mail `coody_codcard_wire` — odstęp nad sekcją „Śledź status zamówienia”.
- Moduł **nie modyfikuje** szablonów `order_conf` sklepu — dane przelewu idą osobnym mailem; placeholdery `{coody_codcard_wire_payment_*}` działają tylko po ręcznej edycji szablonu.

### Naprawiono

- Link do formularza kontaktowego na stronie potwierdzenia zamówienia (`displayOrderConfirmation`) — tag `<a>` poza blokiem `{l mod=…}`, żeby PrestaShop nie escapował HTML.

---

## [1.3.0] — 2025-06-23

### Dodano

- **Osobny mail modułu** `coody_codcard_wire` wysyłany automatycznie po zamówieniu przelewem (szablony w `mails/pl/` i `mails/en/`).
- Hook `actionValidateOrderAfter` — wysyłka danych do przelewu bez ręcznej edycji `order_conf` na sklepie.

### Zmieniono

- Wersja modułu: `1.2.2` → `1.3.0`.
- README — edycja maili sklepu jest opcjonalna (dla jednego maila zamiast dwóch).

---

## [1.2.2] — 2025-06-23

### Usunięto

- Konfigurację **„Treść przy opcji płatności”** dla przelewu (`COODY_CODCARD_WIRE_EXTRA_CONTENT`) — przy przelewie w checkout zostają tylko konto i kwota.

---

## [1.2.1] — 2025-06-23

### Naprawiono

- **Podwójny mail** przy przelewie — własny stan `COODY_OS_WIRE_VALIDATION` bez wysyłki e-mail (zamiast `PS_OS_BANKWIRE` z szablonem `bankwire`).
- **Numer konta w polu „Płatność”** — usunięto dopisywanie do `{payment}`; dane konta w osobnym bloku `{coody_codcard_wire_payment_html}`.
- Szablon `mails/pl/order_conf` — blok z kwotą, kontem i tytułem przelewu pod metodą płatności.
- Fallback dla szablonu `bankwire` — wypełnienie `{bankwire_details}` danymi z modułu (gdyby użyty stary stan).

---

## [1.2.0] — 2025-06-23

### Dodano

- **Druga metoda płatności: przelew bankowy** (`Płatność przelewem`) obok karty przy odbiorze.
- Osobna treść informacyjna przy przelewie (`COODY_CODCARD_WIRE_EXTRA_CONTENT`).
- Stan zamówienia `COODY_OS_WIRE_VALIDATION` (*Oczekiwanie na przelew*).
- Szablony: `paymentOptions-wire-additionalInformation.tpl`, `displayOrderConfirmation-wire.tpl`.
- Ikona `bank-transfer.svg`.
- Parametr `type=card|wire` w kontrolerze `validation`.
- Przelew dostępny także dla koszyków wirtualnych (jeśli skonfigurowane konto).

### Zmieniono

- Wersja modułu: `1.1.0` → `1.2.0`.
- Numery kont i maile z kontem — **tylko dla przelewu**, nie dla karty przy odbiorze.
- Nazwa modułu w BO: *Płatność kartą przy odbiorze i przelewem*.

---

## [1.1.0] — 2025-06-23

### Dodano

- **Numery kont per waluta** — w konfiguracji modułu osobne pole dla każdej aktywnej waluty sklepu (`COODY_CODCARD_ACCOUNTS`).
- Wyświetlanie numeru konta na stronie potwierdzenia zamówienia (`displayOrderConfirmation`).
- Zmienne mailowe `{coody_codcard_account}`, `{coody_codcard_account_text}`, `{coody_codcard_account_html}`.
- Automatyczne rozszerzanie pola `{payment}` w mailu `order_conf` o numer konta.
- Hooki `sendMailAlterTemplateVars` i `actionGetExtraMailTemplateVars` dla pozostałych maili z `{id_order}`.
- `installCurrenciesForAll()` — włączenie modułu dla wszystkich walut przy instalacji / upgrade (jedna instancja modułu zamiast jednej na walutę).
- Skrypt aktualizacji `upgrade/upgrade-1.1.0.php`.
- `README.md` i `CHANGELOG.md`.

### Zmieniono

- Wersja modułu: `1.0.0` → `1.1.0`.
- `validateOrder()` przekazuje `extra_vars` z numerem konta dla waluty koszyka.

---

## [1.0.0] — 2025

### Dodano

- Metoda płatności „Płatność kartą przy odbiorze”.
- Hook `paymentOptions` z konfigurowalną treścią informacyjną (wielojęzyczna).
- Hook `displayOrderConfirmation`.
- Kontroler `validation` finalizujący zamówienie.
- Niestandardowy stan zamówienia `COODY_OS_CODCARD_VALIDATION`.
- Ikona SVG metody płatności.
- Filtrowanie walut (`currencies_mode = checkbox`).
