const getCardPoints = (card) => {
    let cardValue = card.substring(2);
    const valueMap = {
        'J': 11,
        'Q': 12,
        'K': 13,
        'A': 1
    };

    const point = cardValue.replace(/[JQKA]/g, match => valueMap[match]);
    return parseInt(point);
}

const getRummyCardPoints = (card) => {
    // let cardValue = card.substring(2);
    const valueMap = {
        'J': 11,
        'Q': 12,
        'K': 13,
        'A': 14
    };

    const point = card.replace(/[JQKA]/g, match => valueMap[match]);
    return point;
}

const cardPoints = (cards, joker) => {
    let sum = 0;
    for (let index = 0; index < cards.length; index++) {
        const element = cards[index];
        // Joker Point is Zero
        if (element == "JKR1" || element == "JKR2") {
            continue;
        }
        let cardValue = element.replace('_', '').substring(2);
        const jokerValue = joker.replace('_', '').substring(2);

        if (cardValue == jokerValue) {
            continue;
        }

        cardValue = parseInt(cardValue);
        if(isNaN(cardValue)) {
            cardValue = 10;
        }
        sum += (cardValue == 0) ? 10 : cardValue;
    }

    return sum;
}

module.exports = {
    getCardPoints,
    getRummyCardPoints,
    cardPoints
}