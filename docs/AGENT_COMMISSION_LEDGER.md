x# Agent Commission Ledger

## Objectif

Le ledger `agent_commission_entries` fige une commission agent au moment de la reservation, puis historise tous les changements majeurs dans `agent_commission_logs`.

## Cycle de vie

1. `estimated`
   Cree a la vente via `AgentCommissionService::createFromReservation()`.
   Le montant est calcule une fois avec le snapshot des commissions voyage au moment de la reservation.

2. `confirmed`
   Passe a ce statut quand la reservation est confirmee.

3. `payable`
   Passe a ce statut quand la reservation devient reglee et que la commission peut etre mise en paiement.

4. `paid`
   Passe a ce statut uniquement par action finance.

5. `cancelled`
   Passe a ce statut si la reservation est annulee avant paiement de la commission.

6. `reversed`
   Passe a ce statut si la reservation est annulee apres paiement de la commission, ou si une ancienne ecriture doit etre neutralisee apres reaffectation.

## Idempotence

- Une ecriture unique est garantie par `reservation_id + agent_id`.
- Si une ecriture existe deja, le service synchronise seulement le statut et ajoute un log si necessaire.
- Le backfill reutilise la meme logique et ne cree pas de doublons.

## Notes de calcul

- Le service lit les metas WordPress du voyage pour `commission_adulte`, `commission_enfant` et, si disponible, `commission_baby` / `commission_bebe` / `commission_infant`.
- Les anciennes ecritures ne sont pas recalculees lors d'une modification ulterieure de la fiche voyage.
- Les ajustements manuels ne touchent pas le snapshot d'origine `commission_base_amount`; ils modifient uniquement la commission courante et laissent une trace dans les logs.
