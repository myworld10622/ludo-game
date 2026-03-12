<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Betreeno - Card Game Winning Rules</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f8f9fa;
        }
        h1 {
            color: #343a40;
        }
        .rule-section {
            background-color: #fff;
            border-radius: 5px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .rule-section h2 {
            color: #007bff;
        }
        .rule-section p, .rule-section ul, .rule-section ol {
            line-height: 1.6;
        }
        .card-list {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
        }
        .card-container {
            position: relative;
            width: 100px;
            height: 140px;
        }
        .card {
            position: absolute;
            width: 60%;
            height: 60%;
            border: 1px solid #000;
            border-radius: 4px;
            background-size: cover;
            transform-origin: bottom center; /* Set the pivot point to the bottom center */
            transition: transform 0.3s;
            margin:15px;
        }
        .card:nth-child(1) {
            transform: rotate(10deg);
            z-index: 3;
        }
        .card:nth-child(2) {
            transform: rotate(0deg);
            z-index: 2;
        }
        .card:nth-child(3) {
            transform: rotate(-10deg);
            z-index: 1;
        }
        .footer {
            text-align: center;
            padding: 10px;
            background-color: #343a40;
            color: #fff;
            position: fixed;
            width: 100%;
            bottom: 0;
            z-index: 4;
        }
        
    </style>
</head>
<body>
    <h1>Betreeno - Card Game Winning Rules</h1>

    <div class="rule-section">
        <h2>Game Setup</h2>
        <p>Betreeno can be played by two players or more, with each player receiving 3 cards from a standard deck of 52 cards in each round. The winner is determined based on the cards in their hand. The following explains the winning criteria.</p>
    </div>

    <div class="rule-section">
        <h2>Winning Criteria</h2>
        <p>To determine the winner, the following criteria are used:</p>
        <p>In this game, card strength is based on two different criteria: royal hand, where all three cards in the hand are royal cards, and numeric hand, where any one, two, or all three cards are numeric cards.</p>
        <p><strong>Royal Cards:</strong> Ace (A), King (K), 2 (treated the same as King), Queen (Q), Jack (J), and 10. </p>
        <p><strong>Number Cards:</strong> 3, 4, 5, 6, 7, 8, and 9.</p>
        <ul>
            <li><strong>Royal Cards:</strong> The following are the strengths of royal cards from the strongest to weakest:</li>
            <p><strong>Ace in the hand:</strong>the strongest card starts when player hand includes Aces, three Aces, two ace with one other royal card, one ace with two other royal card. </p>
            <ul class="card-list">
                <div class="card-container">
                    <div class="card" style="background-image: url('data/cards/bpa.png');"></div>
                    <div class="card" style="background-image: url('data/cards/rpa.png');"></div>
                    <div class="card" style="background-image: url('data/cards/rsa.png');"></div>
                </div>
                <div class="card-container">
                    <div class="card" style="background-image: url('data/cards/bpa.png');"></div>
                    <div class="card" style="background-image: url('data/cards/rpa.png');"></div>
                    <div class="card" style="background-image: url('data/cards/rsk.png');"></div>
                </div>
                <div class="card-container">
                    <div class="card" style="background-image: url('data/cards/bpa.png');"></div>
                    <div class="card" style="background-image: url('data/cards/rpa.png');"></div>
                    <div class="card" style="background-image: url('data/cards/rs2.png');"></div>
                </div>
                <div class="card-container">
                    <div class="card" style="background-image: url('data/cards/bpa.png');"></div>
                    <div class="card" style="background-image: url('data/cards/rpa.png');"></div>
                    <div class="card" style="background-image: url('data/cards/rsq.png');"></div>
                </div>
                <div class="card-container">
                    <div class="card" style="background-image: url('data/cards/bpa.png');"></div>
                    <div class="card" style="background-image: url('data/cards/rpa.png');"></div>
                    <div class="card" style="background-image: url('data/cards/rsj.png');"></div>
                </div>
                <div class="card-container">
                    <div class="card" style="background-image: url('data/cards/bpa.png');"></div>
                    <div class="card" style="background-image: url('data/cards/rpa.png');"></div>
                    <div class="card" style="background-image: url('data/cards/rs10.png');"></div>
                </div>
                <div class="card-container">
                    <div class="card" style="background-image: url('data/cards/bpa.png');"></div>
                    <div class="card" style="background-image: url('data/cards/rpk.png');"></div>
                    <div class="card" style="background-image: url('data/cards/rsk.png');"></div>
                </div>
                <div class="card-container">
                    <div class="card" style="background-image: url('data/cards/bpa.png');"></div>
                    <div class="card" style="background-image: url('data/cards/rp2.png');"></div>
                    <div class="card" style="background-image: url('data/cards/rs2.png');"></div>
                </div>
                <div class="card-container">
                    <div class="card" style="background-image: url('data/cards/bpa.png');"></div>
                    <div class="card" style="background-image: url('data/cards/rpk.png');"></div>
                    <div class="card" style="background-image: url('data/cards/rsq.png');"></div>
                </div>
                <div class="card-container">
                    <div class="card" style="background-image: url('data/cards/bpa.png');"></div>
                    <div class="card" style="background-image: url('data/cards/rp2.png');"></div>
                    <div class="card" style="background-image: url('data/cards/rsq.png');"></div>
                </div>
                <div class="card-container">
                    <div class="card" style="background-image: url('data/cards/bpa.png');"></div>
                    <div class="card" style="background-image: url('data/cards/rpk.png');"></div>
                    <div class="card" style="background-image: url('data/cards/rsj.png');"></div>
                </div>
                <div class="card-container">
                    <div class="card" style="background-image: url('data/cards/bpa.png');"></div>
                    <div class="card" style="background-image: url('data/cards/rp2.png');"></div>
                    <div class="card" style="background-image: url('data/cards/rsj.png');"></div>
                </div>
                <div class="card-container">
                    <div class="card" style="background-image: url('data/cards/bpa.png');"></div>
                    <div class="card" style="background-image: url('data/cards/rpk.png');"></div>
                    <div class="card" style="background-image: url('data/cards/rs10.png');"></div>
                </div>
                <div class="card-container">
                    <div class="card" style="background-image: url('data/cards/bpa.png');"></div>
                    <div class="card" style="background-image: url('data/cards/rp2.png');"></div>
                    <div class="card" style="background-image: url('data/cards/rs10.png');"></div>
                </div>
                <div class="card-container">
                    <div class="card" style="background-image: url('data/cards/bpa.png');"></div>
                    <div class="card" style="background-image: url('data/cards/rpq.png');"></div>
                    <div class="card" style="background-image: url('data/cards/rsq.png');"></div>
                </div>
                <div class="card-container">
                    <div class="card" style="background-image: url('data/cards/bpa.png');"></div>
                    <div class="card" style="background-image: url('data/cards/rpq.png');"></div>
                    <div class="card" style="background-image: url('data/cards/rsj.png');"></div>
                </div>
                <div class="card-container">
                    <div class="card" style="background-image: url('data/cards/bpa.png');"></div>
                    <div class="card" style="background-image: url('data/cards/rpq.png');"></div>
                    <div class="card" style="background-image: url('data/cards/rs10.png');"></div>
                </div>
                <div class="card-container">
                    <div class="card" style="background-image: url('data/cards/bpa.png');"></div>
                    <div class="card" style="background-image: url('data/cards/rpj.png');"></div>
                    <div class="card" style="background-image: url('data/cards/rsj.png');"></div>
                </div>
                <div class="card-container">
                    <div class="card" style="background-image: url('data/cards/bpa.png');"></div>
                    <div class="card" style="background-image: url('data/cards/rpj.png');"></div>
                    <div class="card" style="background-image: url('data/cards/rs10.png');"></div>
                </div>
                <div class="card-container">
                    <div class="card" style="background-image: url('data/cards/bpa.png');"></div>
                    <div class="card" style="background-image: url('data/cards/rp10.png');"></div>
                    <div class="card" style="background-image: url('data/cards/rs10.png');"></div>
                </div>
            </ul>
            <p><strong>King (k) or card number two (2) in the hand:</strong>When there is no Ace in the hand, then the hand strength will be based on king (k),three kings, two kings with one other royal card, one king with two other royal card. </p>
            <ul class="card-list">
                <div class="card-container">
                    <div class="card" style="background-image: url('data/cards/bpk.png');"></div>
                    <div class="card" style="background-image: url('data/cards/rpk.png');"></div>
                    <div class="card" style="background-image: url('data/cards/rsk.png');"></div>
                </div>
                <div class="card-container">
                    <div class="card" style="background-image: url('data/cards/bpk.png');"></div>
                    <div class="card" style="background-image: url('data/cards/rpk.png');"></div>
                    <div class="card" style="background-image: url('data/cards/rsq.png');"></div>
                </div>
                <div class="card-container">
                    <div class="card" style="background-image: url('data/cards/bpk.png');"></div>
                    <div class="card" style="background-image: url('data/cards/rpk.png');"></div>
                    <div class="card" style="background-image: url('data/cards/rsj.png');"></div>
                </div>
                <div class="card-container">
                    <div class="card" style="background-image: url('data/cards/bpk.png');"></div>
                    <div class="card" style="background-image: url('data/cards/rpk.png');"></div>
                    <div class="card" style="background-image: url('data/cards/rs10.png');"></div>
                </div>
                <div class="card-container">
                    <div class="card" style="background-image: url('data/cards/bpk.png');"></div>
                    <div class="card" style="background-image: url('data/cards/rpq.png');"></div>
                    <div class="card" style="background-image: url('data/cards/rsq.png');"></div>
                </div>
                <div class="card-container">
                    <div class="card" style="background-image: url('data/cards/bpk.png');"></div>
                    <div class="card" style="background-image: url('data/cards/rpq.png');"></div>
                    <div class="card" style="background-image: url('data/cards/rsj.png');"></div>
                </div>
                <div class="card-container">
                    <div class="card" style="background-image: url('data/cards/bpk.png');"></div>
                    <div class="card" style="background-image: url('data/cards/rpq.png');"></div>
                    <div class="card" style="background-image: url('data/cards/rs10.png');"></div>
                </div>
                <div class="card-container">
                    <div class="card" style="background-image: url('data/cards/bpk.png');"></div>
                    <div class="card" style="background-image: url('data/cards/rpj.png');"></div>
                    <div class="card" style="background-image: url('data/cards/rsj.png');"></div>
                </div>
                <div class="card-container">
                    <div class="card" style="background-image: url('data/cards/bpk.png');"></div>
                    <div class="card" style="background-image: url('data/cards/rpj.png');"></div>
                    <div class="card" style="background-image: url('data/cards/rs10.png');"></div>
                </div>
                <div class="card-container">
                    <div class="card" style="background-image: url('data/cards/bpk.png');"></div>
                    <div class="card" style="background-image: url('data/cards/rp10.png');"></div>
                    <div class="card" style="background-image: url('data/cards/rs10.png');"></div>
                </div>
            </ul>
            <p><strong>Queen (Q) in the hand:</strong>When there is no Ace and king in the hand, then the hand strength will be based on Queen (Q),three queens, two queens with one other royal card, one queen with two other royal card. </p>
            <ul class="card-list">
                <div class="card-container">
                    <div class="card" style="background-image: url('data/cards/bpq.png');"></div>
                    <div class="card" style="background-image: url('data/cards/rpq.png');"></div>
                    <div class="card" style="background-image: url('data/cards/rsq.png');"></div>
                </div>
                <div class="card-container">
                    <div class="card" style="background-image: url('data/cards/bpq.png');"></div>
                    <div class="card" style="background-image: url('data/cards/rpq.png');"></div>
                    <div class="card" style="background-image: url('data/cards/rsj.png');"></div>
                </div>
                <div class="card-container">
                    <div class="card" style="background-image: url('data/cards/bpq.png');"></div>
                    <div class="card" style="background-image: url('data/cards/rpq.png');"></div>
                    <div class="card" style="background-image: url('data/cards/rs10.png');"></div>
                </div>
                <div class="card-container">
                    <div class="card" style="background-image: url('data/cards/bpq.png');"></div>
                    <div class="card" style="background-image: url('data/cards/rpj.png');"></div>
                    <div class="card" style="background-image: url('data/cards/rsj.png');"></div>
                </div>
                <div class="card-container">
                    <div class="card" style="background-image: url('data/cards/bpq.png');"></div>
                    <div class="card" style="background-image: url('data/cards/rpj.png');"></div>
                    <div class="card" style="background-image: url('data/cards/rs10.png');"></div>
                </div>
                <div class="card-container">
                    <div class="card" style="background-image: url('data/cards/bpq.png');"></div>
                    <div class="card" style="background-image: url('data/cards/rp10.png');"></div>
                    <div class="card" style="background-image: url('data/cards/rs10.png');"></div>
                </div>
            </ul>
            <p><strong>Jack (J) in the hand:</strong>When there is no Ace, king and Queen in the hand, then the hand strength will be based on Jack (J),three jacks, two jacks with one other royal card, one jack with two other royal card. </p>
            <ul class="card-list">
                <div class="card-container">
                    <div class="card" style="background-image: url('data/cards/bpj.png');"></div>
                    <div class="card" style="background-image: url('data/cards/rpj.png');"></div>
                    <div class="card" style="background-image: url('data/cards/rsj.png');"></div>
                </div>
                <div class="card-container">
                    <div class="card" style="background-image: url('data/cards/bpj.png');"></div>
                    <div class="card" style="background-image: url('data/cards/rpj.png');"></div>
                    <div class="card" style="background-image: url('data/cards/rs10.png');"></div>
                </div>
                <div class="card-container">
                    <div class="card" style="background-image: url('data/cards/bpj.png');"></div>
                    <div class="card" style="background-image: url('data/cards/rp10.png');"></div>
                    <div class="card" style="background-image: url('data/cards/rs10.png');"></div>
                </div>
            </ul>
            <p><strong>Number ten (10) in the hand:</strong>When there is no Ace, king, Queen and Jack in the hand, then the hand strength will be based on (10),three 10s, the weakest royal card. </p>
            <ul class="card-list">
                <div class="card-container">
                    <div class="card" style="background-image: url('data/cards/bp10.png');"></div>
                    <div class="card" style="background-image: url('data/cards/rp10.png');"></div>
                    <div class="card" style="background-image: url('data/cards/rs10.png');"></div>
                </div>
            </ul>
            <li><strong>Numeric cards:</strong> If any of the three cards in a player's hand is not a royal card, points are calculated based on the numeric values of the cards using modulo 10. The sum of the card values is taken, and the last digit of the sum represents the player's points. Royal cards act as supporters, and the hand with the highest supporter wins in case of a tie on points.</li>
            <ul class="card-list">
                <div class="card-container">
                    <div class="card" style="background-image: url('data/cards/bpa.png');"></div>
                    <div class="card" style="background-image: url('data/cards/rpa.png');"></div>
                    <div class="card" style="background-image: url('data/cards/rs9.png');"></div>
                </div>
                <div class="card-container">
                    <div class="card" style="background-image: url('data/cards/bpa.png');"></div>
                    <div class="card" style="background-image: url('data/cards/rp7.png');"></div>
                    <div class="card" style="background-image: url('data/cards/rs8.png');"></div>
                </div>
                <div class="card-container">
                    <div class="card" style="background-image: url('data/cards/bp7.png');"></div>
                    <div class="card" style="background-image: url('data/cards/rp8.png');"></div>
                    <div class="card" style="background-image: url('data/cards/rs9.png');"></div>
                </div>
            </ul>
            <p>There are three hands showen above, first one with only one numeric card (9 points and two strong supporter) this card is the highest numeric hand, second with two numeric cards (7+8=15) using the modulo 10 the point for this hand is 5 with supporter A, and last one all three cards are numeric (7+8+9=24) 4 points without any supporter.</p>
        </ul>
    </div>
</body>
</html>
