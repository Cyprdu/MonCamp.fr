<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <title>Avertissement de sécurité</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <style>
    body {
      margin: 0;
      min-height: 100vh;
      background: radial-gradient(circle at top, #1a0000, #000000 70%);
      color: #ffdddd;
      font-family: Arial, Helvetica, sans-serif;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .warning {
      max-width: 900px;
      background: rgba(0, 0, 0, 0.75);
      border: 2px solid #b30000;
      border-radius: 12px;
      padding: 40px;
      box-shadow: 0 0 40px rgba(179, 0, 0, 0.6);
      animation: pulse 3s infinite;
    }
    @keyframes pulse {
      0% { box-shadow: 0 0 20px rgba(179, 0, 0, 0.4); }
      50% { box-shadow: 0 0 45px rgba(255, 0, 0, 0.8); }
      100% { box-shadow: 0 0 20px rgba(179, 0, 0, 0.4); }
    }
    h1 {
      color: #ff0000;
      text-align: center;
      text-transform: uppercase;
      letter-spacing: 2px;
      margin-bottom: 30px;
    }
    p {
      line-height: 1.7;
      font-size: 1.05rem;
      margin-bottom: 18px;
    }
    .highlight {
      color: #ffffff;
      font-weight: bold;
    }
    .footer {
      margin-top: 30px;
      font-size: 0.9rem;
      color: #ff9999;
      text-align: center;
    }
  </style>
</head>
<body>
  <div class="warning">
    <h1>⚠ Avertissement de Sécurité ⚠</h1>

    <p>
      Nos systèmes de surveillance ont <span class="highlight">détecté une activité hautement suspecte</span> provenant de votre connexion lors de la visite de ce site.
    </p>

    <p>
      Cette activité indique une tentative d’accès à des <span class="highlight">contenus restreints ou non destinés à votre profil</span>. Ce comportement constitue une violation directe de nos conditions d’utilisation et des réglementations en vigueur.
    </p>

    <p>
      Nous vous informons que <span class="highlight">toutes les adresses IP présentant un comportement suspect sont automatiquement enregistrées, horodatées et conservées</span> à des fins de preuve. <br />
      <span class="highlight">Votre adresse IP fait désormais partie de cette liste.</span>
    </p>

    <p>
      <span class="highlight">Toute nouvelle tentative d’accès non autorisé</span>, exploration forcée, contournement de protection ou récupération illégitime de contenu entraînera <span class="highlight">le dépôt immédiat d’une plainte</span> auprès des autorités compétentes, sans avertissement supplémentaire.
    </p>

    <p>
      Ce site est activement surveillé. Les journaux de connexion, empreintes techniques et données de navigation sont analysés en temps réel.
    </p>

    <p class="highlight" style="text-align:center; font-size:1.1rem;">
      CECI EST VOTRE UNIQUE AVERTISSEMENT.
    </p>

    <div class="footer">
      Équipe Sécurité – Système de protection automatisé<br />
      © Tous droits réservés
    </div>
  </div>
</body>
</html>