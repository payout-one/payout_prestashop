# Payout platobný modul

## Podporované prestashop verzie

1.6.1 - 8.x

## Podporované php verzie

php 7.1 a vyššie

## Závislosti

Zapnuté rozšírenie curl pre php.

## Podporované krajiny a meny

### Krajina v adrese doručenia musí byť jedna z podporovaných krajín aby bola Payout platba k dispozícii

| Krajina     | ISO 3166 Alpha-2 kód | ISO 3166 Alpha-3 kód | ISO 3166 numeric-3 Kód |
|-------------|----------------------|----------------------|------------------------|
| Rakúsko     | AT                   | AUT                  | 040                    |
| Belgicko    | BE                   | BEL                  | 056                    |
| Bulharsko   | BG                   | BGR                  | 100                    |
| Chorvátsko  | HR                   | HRV                  | 191                    |
| Cyprus      | CY                   | CYP                  | 196                    |
| Česko       | CZ                   | CZE                  | 203                    |
| Dánsko      | DK                   | DNK                  | 208                    |
| Estónsko    | EE                   | EST                  | 233                    |
| Fínsko      | FI                   | FIN                  | 246                    |
| Francúzsko  | FR                   | FRA                  | 250                    |
| Nemecko     | DE                   | DEU                  | 276                    |
| Grécko      | GR                   | GRC                  | 300                    |
| Maďarsko    | HU                   | HUN                  | 348                    |
| Írsko       | IE                   | IRL                  | 372                    |
| Taliansko   | IT                   | ITA                  | 380                    |
| Lotyšsko    | LV                   | LVA                  | 428                    |
| Litva       | LT                   | LTU                  | 440                    |
| Luxembursko | LU                   | LUX                  | 442                    |
| Malta       | MT                   | MLT                  | 470                    |
| Holandsko   | NL                   | NLD                  | 528                    |
| Poľsko      | PL                   | POL                  | 616                    |
| Portugalsko | PT                   | PRT                  | 620                    |
| Rumunsko    | RO                   | ROU                  | 642                    |
| Slovensko   | SK                   | SVK                  | 703                    |
| Slovinsko   | SI                   | SVN                  | 705                    |
| Španiesko   | ES                   | ESP                  | 724                    |
| Švédsko     | SE                   | SWE                  | 752                    |

### Zvolená mena musí byť jedna z podporovaných mien

| Mena            | ISO 4217 kód |
|-----------------|--------------|
| Euro            | EUR          |
| Česká koruna    | CZK          |
| Maďarský forint | HUF          |
| Poľský zlotý    | PLN          |
| Rumunský lei    | RON          |
| Bulharský lev   | BGN          |

## Inštalácia modulu

Pre inštaláciu modulu sú potrebné nasledujúce kroky:

### Prestashop 1.7, 8

1. V administrácii prestahop-u prejsť do sekcie `Moduly` -> `Manažér modulov`
   ![install first step](views/img/readme/install1sk.png)
2. Stlačiť tlačidlo `Nahrať modul`
   ![install second step](views/img/readme/install2sk.png)
3. Presunúť zip súbor modulu do obĺžnika na nahratie modulu
   ![install third step](views/img/readme/install3sk.png)
4. Po úspešnej inštalácii by ste mali vidieť okno s hláškou o úspechu a tlačidlom na nastavenie modulu
   ![install fourth step](views/img/readme/install4sk.png)

### Prestashop 1.6

1. V administrácii prestashop-u prejsť do sekcie `Moduly a služby`
   ![install first step_16](views/img/readme/install1sk16.png)
2. Stlačiť tlačidlo `Pridať nový modul`
   ![install second step_16](views/img/readme/install2sk16.png)
3. Vybrať súbor modulu stlačením tlačidla `Vybrať súbor` a potvrdiť stlačením tlačidla `Nahrať tento modul`
   ![install third step_16](views/img/readme/install3sk16.png)
4. Inštalovať modul stlačením tlačidla `Inštalovať` pri module `Payout platba` v zozname modulov
   ![install fourth step_16](views/img/readme/install4sk16.png)
5. V dialógovom okne stlačiť tlačidlo `Pokračovať s inštaláciou`
   ![install fifth step_16](views/img/readme/install5sk16.png)
6. Následne by sa mala zobraziť stránka konfigurácie modulu a hláška `Modul(y) boli úspešne nainštalované.`
   ![install sixth step_16](views/img/readme/install6sk16.png)

## Konfigurácia modulu

Pre konfiguráciu modulu je potrebné spraviť niekoľko krokov:

1. V payout administrácii si vytvoriť api kľúč a pri jeho vytváraní vyplniť notifikačnú adresu (notifikačná adresa je
   zobrazená v konfigurácii modulu)
   ![config first step](views/img/readme/config1sk.png)
2. Po vytvorení api kľúča je v konfigurácii modulu potrebné:
    - v prípade testovacieho api kľúča zapnúť prepínačom Sandbox mód
    - v prípade produkčného api kľúča vypnúť prepínačom Sandbox mód
    - zadať klientsky kľúč a tajomstvo

   ![config second step](views/img/readme/config2sk.png)
3. Skontrolovať vyplnené údaje
    - Stlačením tlačidla `Skontrolovať vyplnené údaje`, sa overí platnosť zadaných údajov
        - V prípade úspechu sa zobrazí hláška `Údaje sú platné`
        - V prípade neúspešného overenia sa zobrazí
          hláška `Údaje neboli overené, dôvod: (správa o konkrétnom probléme, ktorá prišla v odpovedi)`
        - Po úspešnom overení, môžeme údaje uložiť - stlačiť tlačidlo `Uložiť`

   ![config third step](views/img/readme/config3sk.png)
4. Skontrolovať výsledok uloženia
    - V prípade úspešného uloženia sa zobrazí hláška `Nastavenia boli úspešne uložené`
      ![config fourth step1](views/img/readme/config4_1sk.png)
    - V prípade neúspešného uloženia sa zobrazí chybová hláška s popisom
      ![config fourth step2](views/img/readme/config4_2sk.png)

> **Upozornenie**: Pri zadaní nesprávnych údajov api kľúča, nebude modul správne fungovať. Dbajte preto na správne
> zadanie údajov a následne otestovanie funkčnosti.

## Administrátorské rozhranie

### Detail objednávky

#### Tabuľka payout logov

V detaile objednávky je k dispozícii prehľad payout logov vo forme tabuľky.

V prestashop 1.7 a 8 je umiestnená takto:
![log_table_1](views/img/readme/log_table1sk.png)

V prestashop 1.6 je umiestnená takto:
![log_table_2](views/img/readme/log_table2sk.png)

Tabuľka obsahuje nasledujúce stĺpce:

- Čas - čas vytvorenia logu
- Id checkout-u - id checkout-u v payout systéme
- Zdroj - na základe dát akej udalosti bol log zapísaný, dva možné zdroje:
    - checkout_response - log sa zapísal po vytvorení checkout-u alebo po zmene stavu objednávky na základe odpovede pre
      dotaz na payout api
    - webhook - log sa zapísal na základe prijatej požiadavky zo strany payout
- Typ - typ udalosti - stavy checkout-u, môžu byť tri možné typy:
    - checkout.created - checkout bol vytvorený v systéme payout
    - checkout.succeeded - checkout bol úspešný
    - checkout.expired - checkout expiroval
- Detail
    - po kliknutí na modré tlačidlo `Detail` sa otvorí okno s detailom logu
    - v detaile je okrem údajov z tabuľky, k dispozícii detail zdrojových dát udalosti, na základe ktorých bol log
      vytvorený

  ![log_table_3](views/img/readme/log_table3sk.png)

#### Stavy objednávky

Pre objednávku vytvorenú so zvolenou payout platobnou možnosťou, existujú tri základné stavy objednávky:

1. `Čakanie na platbu Payout`
    - stav sa nastaví automaticky po vytvorení objednávky
    - objednávku je možné zaplatiť
2. `Platba akceptovaná` - stav sa nastaví po potvrdení úspešného checkout-u prostredníctvom webhook-u prípadne
   prostredníctvom odpovede pre dotaz na payout api
   ![order_state_accepted](views/img/readme/order_state_acceptedsk.png)
3. `Expirovaná Payout platba`
    - stav sa nastaví po potvrdení expirovaného checkout-u prostredníctvom webhook-u prípadne
      prostredníctvom odpovede pre dotaz na payout api
    - objednávku nie je možné už zaplatiť, je ale možné že zaplatená už bola a je potrebné počkať na potvrdenie o
      prijatí platby
    - stav sa stále môže zmeniť na `Platba akceptovaná`

   ![order_state_expired](views/img/readme/order_state_expired.png)

#### Refundácia

V detaile objednávky, je možné vykonať refudáciu prostredníctvom Payout-u. Je možné vykonať ľubovoľný počet refundácii,
ktorých súčet nepresahuje zaplatenú čiastku.

Refundácia sa dá vykonať dvoma rôznymi spôsobmi:

1. Manuálnym zadaním čiastky.
2. Vykonaním prestashop refundácie a zvolenia súbežnej refundácie cez Payout.

##### Manuálna Payout refundácia

Manuála refundácia je k dispozícii ak je stav checkout-u úspešný.

Postup pre refudáciu:

1. Stlačiť tlačidlo `Refundovať cez Payout`.
    - prestashop 1.7. a 8
      ![manual_refund_1_1](views/img/readme/manual_refund1_1sk.png)
    - prestashop 1.6
      ![manual_refund_1_2](views/img/readme/manual_refund1_2sk.png)
2. Otvorí sa dialóg, kde je vidno prehľad refundácii - zaplatená čiastka, refundovaná čiastka a zostávajúca čiastka na
   refundáciu. Pole `Čiastka na refundáciu` sa predvyplní na najvyššiu možnú refudovateľnú čiastku. Túto čiastku je
   možné upravovať až do najvyššej možnej refudovateľnej čiastky.
   ![manual_refund_2](views/img/readme/manual_refund2sk.png)
3. Tlačidlom `Vykonať refund`, sa otvorí potvrdzovacie okno, kde sa refund dá potrvrdiť, prípadne zrušiť.
   ![manual_refund_3](views/img/readme/manual_refund3sk.png)
4. Po potvrdení okna, sa odošle požiadavka na refundáciu a po spracovaní, sa zobrazí správa o výsledku požiadavky.
   ![manual_refund_4](views/img/readme/manual_refund4sk.png)
5. V prípade že došlo k refundovaniu celej zaplatenej čiastky, zmení sa stav objednávky na `Platba bola vrátená` a okno
   objednávky sa nanovo načíta. Po novom načítaní bude navrchu zobrazená správa o úspešnej refundácii.
   ![manual_refund_5](views/img/readme/manual_refund5sk.png)
6. Ak už celá čiastka bola refundovaná, po kliknutí na tlačidlo `Refundovať cez Payout`, sa zobrazí správa o nemožnosti
   ďalšej refundácie.
   ![manual_refund_6](views/img/readme/manual_refund6sk.png)

##### Prestashop refundácia

Súbežna Payout refundácia s prestashop refundáciou(`Bežné vrátenie peňazí` alebo `Vrátenie produktov`) je k dispozícii v
prípade ak je stav checkout-u úspešný.

Postup pre refundáciu:

1. Prejsť do časti `Bežné vrátenie peňazí` alebo `Vrátenie produktov` v detaile objednávky.
   ![presta_refund_1](views/img/readme/presta_refund1sk.png)
2. Vyplniť polia pre prestashop refundáciu.
   ![presta_refund_2](views/img/readme/presta_refund2sk.png)
3. Zaškrtnúť pole `Refundovať cez Payout` (pole `Vygenerovať dobropis` musí byť tiež zaškrtnuté).
   ![presta_refund_3](views/img/readme/presta_refund3sk.png)
4. Stlačiť tlačidlo `Vrátenie produktov`. Po stlačení tlačidla sa zobrazí potvrdzovacie okno so správou o zostávajúcej
   refundovateľnej čiastke. Okno sa dá potrvrdiť, prípadne zrušiť.
   ![presta_refund_4](views/img/readme/presta_refund4sk.png)
5. Po potvrdení okna, sa odošle požiadavka na refundáciu a po spracovaní sa zobrazí správa o výsledku požiadavky.
   ![presta_refund_5](views/img/readme/presta_refund5sk.png)
6. V prípade že došlo k refundovaniu celej zaplatenej čiastky, zmení sa stav objednávky na `Platba bola vrátená`.
   ![presta_refund_6](views/img/readme/presta_refund6sk.png)

##### Tabuľka záznamov o refundáciach

Podobne ako [Tabuľka payout logov](#tabuľka-payout-logov), je v detaile objednávky je k dispozícii prehľad informácii o
refundáciach vo forme tabuľky.

V prestashop 1.7 a 8 je umiestnená takto:
![refund_table_1](views/img/readme/refund_table1sk.png)

V prestashop 1.6 je umiestnená takto:
![refund_table_2](views/img/readme/refund_table2sk.png)

Tabuľka obsahuje nasledujúce stĺpce:

- Čas - čas vytvorenia záznamu
- Id checkout-u - id checkout-u v payout systéme
- Zamestnanec - meno, priezvisko, email a id zamestnanca, ktorý vykonal refundáciu
- Id výberu(withdrawal id)
- Čiastka
- Detail
    - po kliknutí na modré tlačidlo `Detail` sa otvorí okno s detailom záznamu
    - v detaile je okrem údajov z tabuľky, k dispozícii detail odpovede z refundácie, na základe ktorých bol záznam
      vytvorený

  ![refund_table_3](views/img/readme/refund_table3sk.png)

### Prestashop logy

- Modul v prípade chýb alebo potreby upozornenia loguje do prestashop log tabuľky
- K prestashop logom je možné pristupovať prostredníctvom sekcie
  administrácie `Rozšírené nastavenia` -> `Záznamy aktivity`
- Všetky payout logy majú prefix v tvare [Payout] a je ich možné podľa toho filtrovať

![prestashop_log](views/img/readme/prestashop_logsk.png)
