# IndieSoft WooCommerce Recesso

## Requisiti recepiti

- Dal 19 giugno 2026 gli ecommerce devono offrire una funzione digitale dedicata per l'esercizio del recesso online, secondo il nuovo art. 54-bis del Codice del Consumo citato nel materiale di riferimento.
- Il pulsante deve essere chiaro, diretto e facilmente accessibile nello stesso spazio digitale in cui e stato effettuato l'ordine.
- Non basta aggiornare le condizioni generali di vendita: serve una procedura operativa.
- Il consumatore deve poter indicare o confermare nome, estremi dell'ordine, beni/servizi acquistati e recapito elettronico.
- Deve essere presente una conferma esplicita della volonta di recedere.
- La ricevuta deve essere inviata senza ritardo su supporto durevole, tipicamente via email, con testo della dichiarazione e data/ora di ricezione.

## Funzioni del plugin

- Pulsante "Recedere dal contratto qui" sugli ordini idonei.
- Endpoint configurabile in "Il mio account".
- Pulsante anche nelle pagine di dettaglio ordine e thank-you.
- Shortcode `[indiesoft_recesso]` per inserire la funzione in una pagina dedicata.
- Supporto ordini guest tramite numero ordine + email, con link firmato tramite order key.
- Finestra temporale configurabile, default 14 giorni.
- Stati ordine idonei configurabili.
- Motivi, metodi di rimborso e testi personalizzabili dal pannello admin.
- Conferma obbligatoria della dichiarazione di recesso.
- Email di ricevuta con dichiarazione e timestamp.
- Registro richieste in tabella dedicata e gestione stato da back office.
- Compatibilita HPOS dichiarata tramite API WooCommerce.

## Configurazione consigliata

1. Attivare il plugin.
2. Aprire `Recesso > Impostazioni`.
3. Verificare endpoint, giorni disponibili, stati idonei e testi.
4. Creare una pagina visibile nel sito con shortcode `[indiesoft_recesso]` se si vuole un accesso pubblico aggiuntivo oltre alle pagine ordine/account.
5. Salvare i permalink dopo eventuali modifiche all'endpoint.
