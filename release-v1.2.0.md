# Release v1.2.0

Cette version consolide la securite et la fiabilite des flux admin, guest et moderator.

Points cles:
- Stabilisation du CRUD admin des pieces (regles vente/echange, validations prix, robustesse update)
- Harmonisation de la validation des descriptions annonces cote admin
- Renforcement des tests de non-regression admin et guest (pieces, messages, annonces)
- Durcissement du flux moderator (suppression de routes dupliquees, scenarios CSRF et validation image)
- Scenarios CSRF et controle de propriete renforces
- Validation complete de la suite de tests: 66 tests, 184 assertions
- Documentation README mise a jour avec le suivi des lots et de la dette technique
