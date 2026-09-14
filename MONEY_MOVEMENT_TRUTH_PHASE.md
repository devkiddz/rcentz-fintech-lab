RCENTZ MONEY MOVEMENT TRUTH + HISTORY PHASE

INSTALL
1. Extract over C:\xampp\htdocs\tesla.com
2. Run: /c/xampp/php/php.exe artisan migrate
3. Run: /c/xampp/php/php.exe artisan optimize:clear
4. Run: npm run build

IMPORTANT: Use normal migrate. Do NOT run migrate:fresh.

TRUTH MODEL
Wallet Balance = cash owned.
Reserved Balance = cash locked by pending outbound requests.
Available Balance = Wallet Balance - Reserved Balance.
Total Assets = Wallet Balance + current stock value + current investment value.

DEPOSIT: submitted -> pending -> admin approve -> credit -> completed. No instant credit before approval.
WITHDRAWAL: request -> reserve -> pending -> approve settles reservation/debits, reject releases reservation.
TRANSFER: sender+recipient locked atomically; sender debit and recipient credit share a reference.
STOCK/INVESTMENT: buys use available balance under wallet lock; sales credit wallet and write history.
HISTORY: /account/history. Ledger rows link to history by reference. Existing ledger rows are backfilled into history.

TEST CHECKPOINT
A) Request $100 withdrawal: wallet balance unchanged, reserved +100, available -100, pending debit, history event. Reject: reservation releases. Repeat and approve: reserved falls and wallet balance settles -100.
B) Submit $250 deposit: no balance increase before approval; admin approval credits it.
C) Amara -> Daniel $50: Amara -50 debit, Daniel +50 credit, shared reference, both history entries.
D) Buy then sell 1 AAPL: available balance and history must track stock.buy / stock.sell.

Stop at the first failed checkpoint and send the exact error/screen.
