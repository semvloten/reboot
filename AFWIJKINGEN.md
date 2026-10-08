# Reboot – afwijkingen bij oplevering

De volgende verschillen tussen de projectbriefing, planning en uitwerking zijn van toepassing:

- **Checkout (FE-12, T-21/T-22):** de planning noemt reserveren en kopen. De behouden betaalpagina bevestigt alleen een reservering; er wordt geen geld afgeschreven. Echte betalingen vallen buiten de MVP en de pagina is behouden.
- **Winkelweergave (FE-11/FE-12, RV-05, T-18/T-19):** naast beschikbare, goedgekeurde apparaten blijven gereserveerde apparaten zichtbaar met hun status. Ze kunnen niet opnieuw worden gereserveerd. Afgekeurde apparaten en apparaten die nog op keuring of herstel wachten, worden niet getoond.
- **Wachtwoordopslag (RV-03, TE-03, T-27):** de planning noemt versleuteling. De applicatie gebruikt wachtwoordhashes via Laravel. Hashes zijn niet terug te ontsleutelen en passen bij veilige wachtwoordopslag.
- **Keuring (FE-07/FE-10, T-12/T-13 en T-15/T-16):** de checklist verschilt per apparaattype. Bij consoles worden batterij en ingebouwd scherm niet gecontroleerd; daarvoor wordt de videopoort vastgelegd. De status ‘herstel nodig’ heet in de interface ‘Onderhoud nodig’ en heeft dezelfde betekenis.
- **Prioriteren en toewijzen (FE-06, T-12/T-13):** deze functies zijn gebouwd als onderdeel van het keurmeesteroverzicht. Ze staan in de projectbriefing, maar hebben geen afzonderlijk eis- of taaknummer in de oorspronkelijke planning. Dit staat ook in de codecomments uitgelegd.
- **Navigatie (RV-06, TE-06, T-31):** de link ‘Winkel’ is verborgen op de inlog- en registratiepagina. Op schermen smaller dan 1024 pixels staan de navigatielinks in een compact uitklapmenu; op grotere schermen blijven ze naast elkaar staan. Dit maakt de navigatie overzichtelijker en het gebruik op een telefoon makkelijker, doordat de knoppen minder ruimte innemen en de links bij elkaar staan.
- **Planning (T-34):** de oorspronkelijke einddatum was 1 oktober 2026. Tot en met 8 oktober 2026 zijn nog verbeteringen aangebracht, waaronder het blokkeren en herstellen van zelfreserveringen en het aanvullen van de installatiehandleiding en verwijzingen naar eisen. De uitvoering liep daarmee langer door dan oorspronkelijk gepland.
