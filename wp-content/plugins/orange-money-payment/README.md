# Orange Money Payment Gateway for WooCommerce

Plugin WordPress professionnel pour intégrer Orange Money comme moyen de paiement dans WooCommerce.

## Fonctionnalités

- Intégration complète de l'API Orange Money Web Payment
- Support du mode Sandbox (test) et Production
- Gestion automatique des tokens OAuth2 (durée de vie: 90 jours)
- Webhooks pour les notifications de paiement en temps réel
- Vérification du statut des transactions
- Logs détaillés pour le débogage
- Interface d'administration intuitive
- Support multilingue (FR/EN)
- Sécurité renforcée avec validation des tokens
- Compatible WooCommerce 5.0+
- Support des blocs WooCommerce (Gutenberg checkout)

## Prérequis

- WordPress 5.8+
- WooCommerce 5.0+
- PHP 7.4+
- Compte développeur Orange: https://developer.orange.com
- Certificat SSL (HTTPS) pour la production

## Comportement des URLs selon l'environnement

Le plugin détecte automatiquement votre environnement et adapte son comportement :

### En environnement local (localhost) + Mode Test
- Les URLs (return_url, cancel_url, notif_url) sont automatiquement remplacées par httpbin.org
- Raison : L'API Orange Money rejette les URLs localhost
- Vous verrez les réponses JSON sur httpbin.org au lieu de revenir sur votre site

### Sur site hébergé (production ou test)
- Les URLs réelles de votre site sont utilisées
- Le client revient sur votre site après paiement
- Les webhooks fonctionnent normalement

### En mode Production
- Les URLs réelles sont toujours utilisées, quel que soit l'environnement
- Assurez-vous que votre site est accessible publiquement en HTTPS

## Installation

### Étape 1: Installation du plugin

#### Option A: Installation manuelle

1. Téléchargez le dossier du plugin
2. Placez-le dans `wp-content/plugins/`
3. Allez dans WordPress Admin > Extensions
4. Activez "Orange Money Payment Gateway"

#### Option B: Via l'admin WordPress

1. Allez dans Extensions > Ajouter
2. Téléversez le fichier ZIP du plugin
3. Cliquez sur "Activer"

### Étape 2: Obtenir les identifiants API

#### 2.1 Créer un compte développeur

1. Allez sur https://developer.orange.com
2. Cliquez sur "Sign Up" ou "S'inscrire"
3. Remplissez le formulaire d'inscription
4. Validez votre email

#### 2.2 Créer une application

1. Connectez-vous à https://developer.orange.com
2. Allez dans "My Apps" ou "Mes Applications"
3. Cliquez sur "Add a new app" ou "Créer une application"
4. Donnez un nom à votre application (ex: "Ma Boutique WooCommerce")
5. Notez votre **Client ID** et **Client Secret**

#### 2.3 Souscrire à l'API Orange Money WebPay

1. Dans votre application, cliquez sur "Add API"
2. Recherchez "Orange Money WebPay Dev" (pour le test)
3. Cliquez sur "Subscribe" ou "Souscrire"
4. Remplissez le formulaire avec:
   - **Merchant Account Number**: Fourni par Orange (ex: 7701900100)
   - **Merchant Code**: Fourni par Orange (ex: 101021)
5. Validez la souscription
6. Notez votre **Merchant Key** (8 caractères hexadécimaux)

**Important**: Si vous n'avez pas reçu vos identifiants de test (Merchant Account Number, Merchant Code, PIN), contactez Orange Money à l'adresse fournie dans la documentation.

### Étape 3: Configuration du plugin

#### 3.1 Accéder aux paramètres

1. Allez dans WooCommerce > Paramètres
2. Cliquez sur l'onglet "Paiements"
3. Trouvez "Orange Money" dans la liste
4. Cliquez sur "Gérer" ou le bouton de configuration

#### 3.2 Configuration de base

**Activer le plugin:**
- Cochez "Activer Orange Money"

**Titre et description:**
- **Titre**: "Orange Money" (ou personnalisez)
- **Description**: "Payez en toute sécurité avec Orange Money"

**Mode Test:**
- Cochez "Activer le mode test (Sandbox)" pour commencer

#### 3.3 Identifiants de Test (Sandbox)

Remplissez les champs suivants avec vos identifiants de test:

```
Client ID (Test): [Votre Client ID]
Client Secret (Test): [Votre Client Secret]
Merchant Key (Test): [Votre Merchant Key - 8 caractères]
```

**Exemple de Merchant Key**: `a86b2087`

#### 3.4 Paramètres avancés

- **Code Pays**: Sélectionnez votre pays (pour la production)
- **Langue de Paiement**: Français ou Anglais
- **Mode Debug**: Activé (recommandé pour les tests)

#### 3.5 Sauvegarder

Cliquez sur "Enregistrer les modifications"

### Étape 4: Test de connexion

1. Dans les paramètres Orange Money
2. Cliquez sur le bouton "Test de Connexion"
3. Vous devriez voir: "Connexion réussie à l'API Orange Money"

Si le test échoue:
- Vérifiez vos identifiants
- Assurez-vous d'utiliser les identifiants de TEST
- Consultez les logs (WooCommerce > Status > Logs)

### Étape 5: Test de paiement

#### 5.1 Créer une commande de test

1. Allez sur votre boutique
2. Ajoutez un produit au panier
3. Procédez au checkout
4. Sélectionnez "Orange Money" comme moyen de paiement
5. Cliquez sur "Commander"

#### 5.2 Simuler le paiement

Vous serez redirigé vers la page de paiement Orange Money (Sandbox).

**Pour obtenir un code OTP de test:**

1. Allez sur le simulateur USSD: https://mpayment.orange-money.com/mpayment-otp/login
2. Connectez-vous avec:
   - **Login**: Votre Merchant Account Number (ex: 7701900100)
   - **Password**: Votre Channel User ID
3. Demandez un OTP avec le PIN du subscriber
4. Copiez le code OTP
5. Retournez sur la page de paiement
6. Entrez le numéro du subscriber (ex: 7701100100)
7. Entrez le code OTP
8. Cliquez sur "Confirmer"

#### 5.3 Vérifier la commande

1. Retournez dans WordPress Admin
2. Allez dans WooCommerce > Commandes
3. Votre commande devrait être marquée comme "Terminée" ou "En cours"
4. Vérifiez les notes de commande pour voir les détails du paiement

## Passage en Production

### Étape 1: Obtenir les identifiants de production

1. Contactez votre représentant Orange Money local
2. Demandez l'activation de votre compte marchand en production
3. Obtenez vos identifiants de production:
   - Client ID (Production)
   - Client Secret (Production)
   - Merchant Key (Production)

### Étape 2: Configuration production

1. Allez dans les paramètres Orange Money
2. **Décochez** "Activer le mode test (Sandbox)"
3. Remplissez les identifiants de production:

```
Client ID (Production): [Votre Client ID Prod]
Client Secret (Production): [Votre Client Secret Prod]
Merchant Key (Production): [Votre Merchant Key Prod]
```

4. Sélectionnez votre **Code Pays**
5. Sauvegardez les modifications

### Étape 3: Vérifications de sécurité

Avant de passer en production, vérifiez:

- Votre site utilise HTTPS (certificat SSL valide)
- L'URL de notification est accessible publiquement
- Les logs sont activés pour surveiller les transactions
- Vous avez testé plusieurs scénarios de paiement
- Les webhooks fonctionnent correctement

## Architecture du Plugin

### Structure des fichiers

```
orange-money-payment/
├── orange-money-payment.php         # Fichier principal du plugin
├── includes/                        # Classes PHP du plugin
│   ├── class-om-api-client.php      # Client API Orange Money
│   ├── class-om-payment-gateway.php # Passerelle de paiement WooCommerce
│   ├── class-om-webhook-handler.php # Gestionnaire de webhooks
│   ├── class-om-ajax-handler.php    # Gestionnaire des requêtes AJAX
│   ├── class-om-logger.php          # Système de logs
│   ├── class-om-hooks.php           # Hooks et actions personnalisées
│   └── class-om-blocks-support.php  # Support des blocs WooCommerce
├── assets/                          # Ressources statiques
│   ├── css/
│   │   ├── admin.css                # Styles pour l'administration
│   │   └── frontend.css             # Styles pour le frontend
│   ├── js/
│   │   ├── admin.js                 # JavaScript pour l'administration
│   │   └── blocks.js                # JavaScript pour les blocs WooCommerce
│   └── images/
│       └── orange-money-logo.png    # Logo Orange Money
├── languages/                       # Fichiers de traduction
├── readme.txt                       # Description pour le répertoire WordPress
└── README.md                        # Documentation complète
```

### Rôle de chaque fichier dans includes/

#### class-om-api-client.php
**Rôle**: Client API pour communiquer avec Orange Money
**Responsabilités**:
- Gestion de l'authentification OAuth2
- Création des sessions de paiement
- Vérification du statut des transactions
- Gestion du cache des tokens d'accès
- Communication sécurisée avec l'API Orange Money

**Méthodes principales**:
- `get_access_token()`: Obtient et cache le token OAuth2
- `create_payment()`: Crée une session de paiement
- `get_transaction_status()`: Vérifie le statut d'une transaction
- `test_connection()`: Teste la connexion à l'API

#### class-om-payment-gateway.php
**Rôle**: Passerelle de paiement WooCommerce
**Responsabilités**:
- Intégration avec le système de paiement WooCommerce
- Interface d'administration pour la configuration
- Traitement des commandes et redirection vers Orange Money
- Gestion des paramètres du plugin
- Affichage du moyen de paiement au checkout

**Méthodes principales**:
- `process_payment()`: Traite une commande et redirige vers Orange Money
- `is_available()`: Détermine si le moyen de paiement est disponible
- `init_form_fields()`: Définit les champs de configuration
- `admin_options()`: Affiche l'interface d'administration

#### class-om-webhook-handler.php
**Rôle**: Gestionnaire des notifications de paiement
**Responsabilités**:
- Réception des notifications webhook d'Orange Money
- Validation de l'authenticité des notifications
- Mise à jour du statut des commandes
- Gestion des différents statuts de paiement (SUCCESS, FAILED, PENDING)

**Méthodes principales**:
- `handle()`: Point d'entrée pour traiter les webhooks
- `process_success()`: Traite les paiements réussis
- `process_failure()`: Traite les paiements échoués
- `find_order_by_notif_token()`: Trouve une commande par son token de notification

#### class-om-ajax-handler.php
**Rôle**: Gestionnaire des requêtes AJAX
**Responsabilités**:
- Test de connexion API depuis l'administration
- Vérification du statut des paiements en temps réel
- Actions administratives asynchrones

**Méthodes principales**:
- `test_connection()`: Teste la connexion API via AJAX
- `check_payment_status()`: Vérifie le statut d'un paiement via AJAX

#### class-om-logger.php
**Rôle**: Système de logs centralisé
**Responsabilités**:
- Enregistrement des événements du plugin
- Masquage des données sensibles dans les logs
- Intégration avec le système de logs WooCommerce
- Différents niveaux de log (info, error, debug, warning)

**Méthodes principales**:
- `info()`, `error()`, `debug()`, `warning()`: Méthodes de logging
- `log_api_request()`: Log spécialisé pour les requêtes API
- `sanitize_log_data()`: Masque les données sensibles

#### class-om-hooks.php
**Rôle**: Hooks et actions personnalisées
**Responsabilités**:
- Ajout de métaboxes dans l'administration des commandes
- Actions personnalisées pour les commandes Orange Money
- Colonnes personnalisées dans la liste des commandes
- Interface pour vérifier le statut des paiements

**Méthodes principales**:
- `add_order_meta_box()`: Ajoute une métabox aux commandes
- `process_check_status()`: Action pour vérifier le statut
- `render_order_columns()`: Affiche les colonnes personnalisées

#### class-om-blocks-support.php
**Rôle**: Support des blocs WooCommerce (Gutenberg)
**Responsabilités**:
- Intégration avec le nouveau système de checkout par blocs
- Enregistrement du moyen de paiement pour les blocs
- Configuration des scripts JavaScript pour les blocs

**Méthodes principales**:
- `initialize()`: Initialise le support des blocs
- `get_payment_method_script_handles()`: Définit les scripts nécessaires
- `get_payment_method_data()`: Fournit les données pour les blocs

## Parcours d'un Paiement

### 1. Initialisation du Paiement
1. **Client** sélectionne Orange Money au checkout
2. **WooCommerce** appelle `WC_Orange_Money_Gateway::process_payment()`
3. **Gateway** utilise `OM_API_Client::create_payment()` pour créer une session
4. **API Client** s'authentifie avec OAuth2 si nécessaire
5. **API Client** envoie la requête à Orange Money
6. **Orange Money** retourne un `pay_token` et `payment_url`
7. **Gateway** sauvegarde les données et redirige le client

### 2. Paiement chez Orange Money
1. **Client** est redirigé vers la page Orange Money
2. **Client** saisit son numéro de téléphone
3. **Orange Money** envoie un SMS avec code OTP
4. **Client** saisit le code OTP
5. **Orange Money** traite le paiement

### 3. Notification de Retour
1. **Orange Money** envoie un webhook à `/?wc-api=wc_orange_money_gateway`
2. **WordPress** route la requête vers `WC_Orange_Money_Gateway::webhook()`
3. **Gateway** instancie `OM_Webhook_Handler` et appelle `handle()`
4. **Webhook Handler** valide le `notif_token`
5. **Webhook Handler** trouve la commande correspondante
6. **Webhook Handler** met à jour le statut selon la réponse (SUCCESS/FAILED/PENDING)
7. **WooCommerce** déclenche les actions appropriées (emails, stock, etc.)

### 4. Retour du Client
1. **Orange Money** redirige le client vers `return_url`
2. **Gateway** vérifie le statut sur la page de remerciement
3. **Client** voit la confirmation de commande

## URLs de notification

Le plugin configure automatiquement l'URL de notification:
```
https://votresite.com/?wc-api=wc_orange_money_gateway
```

Cette URL doit être accessible publiquement pour recevoir les notifications de paiement.

## Flux de données

### Base de données
Le plugin crée une table personnalisée `wp_orange_money_transactions` qui stocke:
- `order_id`: ID de la commande WooCommerce
- `pay_token`: Token de paiement Orange Money
- `notif_token`: Token de notification pour validation
- `txnid`: ID de transaction Orange Money (après paiement)
- `status`: Statut actuel (INITIATED, PENDING, SUCCESS, FAILED)
- `amount`: Montant du paiement
- `currency`: Devise utilisée
- `created_at`, `updated_at`: Horodatage

### Métadonnées de commande
Le plugin stocke également des métadonnées sur chaque commande:
- `_om_pay_token`: Token de paiement
- `_om_notif_token`: Token de notification
- `_om_order_id`: ID de commande Orange Money
- `_om_payment_url`: URL de paiement
- `_om_txnid`: ID de transaction (après paiement)

## Sécurité

### Validation des webhooks
Le plugin valide chaque notification en vérifiant:
- Le `notif_token` correspond à celui stocké
- La structure JSON est valide
- Les champs requis sont présents

### Données sensibles
- Les tokens d'accès sont stockés de manière sécurisée
- Les logs masquent les informations sensibles
- Communication HTTPS uniquement

## Statuts de transaction

| Statut | Description |
|--------|-------------|
| `INITIATED` | Paiement initié, en attente de l'utilisateur |
| `PENDING` | Utilisateur a confirmé, traitement en cours |
| `SUCCESS` | Paiement réussi |
| `FAILED` | Paiement échoué |
| `EXPIRED` | Token expiré (validité: 10 minutes) |

## Débogage

### Activer les logs

1. Allez dans les paramètres Orange Money
2. Cochez "Mode Debug"
3. Consultez les logs dans WooCommerce > Status > Logs
4. Recherchez les fichiers commençant par "orange-money-payment"

### Tester la connexion API

1. Dans les paramètres du plugin
2. Cliquez sur "Test de Connexion"
3. Vérifiez que la connexion est réussie

### Problèmes courants

**Erreur: "Failed to get access token"**
- Vérifiez vos Client ID et Client Secret
- Assurez-vous d'utiliser les bons identifiants (test/production)

**Erreur: "Payment creation failed"**
- Vérifiez votre Merchant Key
- Assurez-vous que le compte marchand est actif
- Vérifiez les logs pour plus de détails

**Webhook non reçu**
- Vérifiez que votre site est accessible publiquement
- Testez l'URL webhook manuellement
- Vérifiez les logs du serveur web

## Pays supportés

- Côte d'Ivoire (CI)
- Sénégal (SN)
- Mali (ML)
- Burkina Faso (BF)
- Cameroun (CM)
- Madagascar (MG)
- RD Congo (CD)
- Guinée (GN)

## API Reference

### Endpoints utilisés

**Token OAuth2**
```
POST https://api.orange.com/oauth/v3/token
```

**Création de paiement (Sandbox)**
```
POST https://api.orange.com/orange-money-webpay/dev/v1/webpayment
```

**Statut de transaction (Sandbox)**
```
POST https://api.orange.com/orange-money-webpay/dev/v1/transactionstatus
```

## Dépannage

### Problème: "Failed to get access token"

**Solutions:**
1. Vérifiez vos Client ID et Client Secret
2. Assurez-vous d'utiliser les bons identifiants (test/prod)
3. Vérifiez que votre application est active sur developer.orange.com
4. Consultez les logs pour plus de détails

### Problème: "Payment creation failed"

**Solutions:**
1. Vérifiez votre Merchant Key (format: 8 caractères hexadécimaux)
2. Assurez-vous que votre compte marchand est actif
3. Vérifiez que vous utilisez la bonne devise (OUV en test)
4. Consultez les logs pour le message d'erreur exact

### Problème: Webhook non reçu

**Solutions:**
1. Vérifiez que votre site est accessible publiquement
2. Testez l'URL webhook avec un outil comme Postman
3. Vérifiez les logs du serveur web (Apache/Nginx)
4. Assurez-vous qu'aucun firewall ne bloque les requêtes
5. Vérifiez que mod_rewrite est activé (pour les permaliens WordPress)

## Checklist de mise en production

- [ ] Site en HTTPS avec certificat SSL valide
- [ ] Identifiants de production configurés
- [ ] Mode test désactivé
- [ ] Test de connexion API réussi
- [ ] Paiement de test effectué avec succès
- [ ] Webhook reçu et traité correctement
- [ ] Logs activés pour surveillance
- [ ] Sauvegarde de la base de données effectuée
- [ ] Documentation lue et comprise
- [ ] Support Orange Money contacté si nécessaire

## Changelog

### Version 1.0.0
- Version initiale
- Support complet de l'API Orange Money Web Payment v1
- Mode Sandbox et Production
- Gestion des webhooks
- Interface d'administration
- Support des blocs WooCommerce

## Support

- Documentation API: https://developer.orange.com
- Email support: support@orangemoney.com

