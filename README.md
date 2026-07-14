# Notifer: assessment

# Uitwerking

## Design note
-> Overwegingen
- met het oog op pieklasten vangen we betalingsnotificaties op in een redis queue achter het externe eindpunt. Een apart proces eet deze queue leeg om het verwerkingsproces mee uit te voeren.
- een dossier, te vinden via het id, kan een betaling accepteren.
- het dossier stuurt een event DEELBETALING wanneer de verwerkte betaling een positief saldo achterlaat, of een event AFBETAALD wanneer het saldo nul is geworden. Het event bevat het bedrag en het nieuwe saldo als payload.
- een notificatie bevat tenminste een dossier-id en een bedrag. Het bedrag is positief, en in centen.
- ongeldige notificaties gaan naar een failed-log. Dit laat ik buiten de eerste iteratie.
- om dubbele verwerking te voorkomen, heb ik iets nodig dat de notificatie uniek identificeert, in ieder geval binnen een dossier.
  Is de betaaltijd gegeven, en vinden we die resolutie nauwkeurig genoeg (doet iemand ooit twee deelbetalingen met gelijk bedrag in dezelfde seconde?), dan kunnen we die gebruiken.
- Bij het dossier hoort een tabel van geaccepteerde betalingen, met unieke index.
  Wordt de nieuwe betaling met succes ingelegd, dan volgt het nieuwe saldo uit deze tabel. Betaling inleggen levert een sequentieel volgnummer op; het saldo volgt uit een somquery binnen het dossier tot en met dit volgnummer.
- de locking-strategie veroorzaakt een bottleneck, waardoor het verwerkingsproces langzamer kan lopen dan er notificaties binnenkomen;
  de aanvoer-queue zou dit kunnen signaleren, en slim partitioneren van de database kan de bottleneck verkleinen. Performance-monitoring moet uitwijzen of deze ingrepen nodig zijn. SQL Server staat hier beslist boven SQLite.
- is een betaling groter dan het dossier-saldo, dan moet het restant worden terugbetaald. Dit laat ik buiten de eerste iteratie.
- voor bevestigingen naar schuldenaren draait een listener voor de events DEELBETALING en AFBETAALD.
  Dit proces schrijft bevestigingen naar een of meer verzend-queues (email, sms), al naar blijkt uit de contact-afspraken in het dossier.
- een apart proces eet de verzend-queues leeg tussen 7:00 en 20:00 ma t/m za.

# Opdracht (ter referentie)

## Context
Wij verwerken betalingsnotificaties die binnenkomen van externe partijen (banken, betaalproviders,
ketenpartners). Elke notificatie hoort bij een dossier. De verwerking moet betrouwbaar zijn: het saldo
wordt bijgewerkt, vervolgacties worden bepaald (bijv. dossier afsluiten bij volledige betaling,
betalingsregeling bijwerken) en de schuldenaar ontvangt een bevestiging.

## Schaal en randvoorwaarden:
- ±50.000 notificaties per dag, met pieken rond 09:00 en na batchruns van banken ('s nachts)
- Externe partijen sturen notificaties soms dubbel, soms te laat, soms met afwijkende bedragen
(deelbetalingen)
- Bevestigingen naar schuldenaren mogen wettelijk alleen verstuurd worden ma–za tussen 07:00 en
20:00
- Eén betaling mag nooit twee keer verwerkt worden — de financiële administratie moet altijd
kloppen
- Stack: Laravel, Filament (backoffice), SQL, Redis/Horizon voor queues

## De opdracht (twee delen):

1. Design note (max. 2 A4 of vergelijkbaar in README-vorm)
Beschrijf jouw ontwerp voor dit systeem. Behandel in elk geval:
- De verwerkingsflow van binnenkomst tot bevestiging, en waar je welke garanties afdwingt
(idempotency, volgorde, consistentie)
- Hoe je omgaat met de pieken en met falende verwerking
- Jouw teststrategie: wat test je op unit-, feature- en integratieniveau, en wat bewust niet?
- Wat je in een eerste iteratie bewust niet bouwt, en waarom
- Welke architectuur kies je, welk platform, software, library keuzes

2. Kernimplementatie
Implementeer alléén het deel dat jouw belangrijkste ontwerpkeuze aantoont — het stuk waarvan jij
zegt: "als dít niet klopt, klopt niets." Bijvoorbeeld de idempotente verwerking, de saldomutatie onder
concurrency, of de gescheiden verwerking/notificatie-flow. Inclusief de test(s) die deze keuze
bewijzen.
Het hoeft géén draaiende totaalapplicatie te zijn. We beoordelen de kwaliteit van je keuzes en de
diepgang van het kernstuk, niet de volledigheid.

## Randvoorwaarden
- Laravel 11, 12 of 13; SQLite is prima als database-substituut, benoem in je design note waar SQL
Server-specifieke overwegingen zouden spelen.
- AI-tools zijn toegestaan en zelfs aangemoedigd — we werken er zelf ook mee. In het gesprek
verwachten we wel dat je elke keuze kunt verdedigen, ook van gegenereerde code.
- Richttijd: 2 à 3 uur totaal. Bewaak dit zelf; de scope is bewust groter dan de tijd. Prioriteren is
onderdeel van de opdracht.
