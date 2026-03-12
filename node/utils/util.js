const { v4: uuidv4 } = require('uuid');
class UtilHelper {
    getRandomNumber(min, max) {
        return Math.floor(Math.random() * (max - min + 1)) + min;
    }

    async getAmountByPercentage(amount, percentage) {
        const singleUnitPrice = (percentage / 100).toFixed(2);
        return (amount * parseFloat(singleUnitPrice)).toFixed(2);
    }

    getRoundNumber(amount, roundNumber) {
        return amount.toFixed(roundNumber);
    }

    getAttributes(attributes) {
        return attributes.length > 0 ? { attributes } : {};
    }

    async getAmountByPercentageWithoutRound(amount, percentage) {
        const singleUnitPrice = (percentage / 100);
        return (amount * parseFloat(singleUnitPrice)).toFixed(2);
    }

    generateUniqueNumber() {
        const uuid = uuidv4();
        return uuid.replace(/-/g, '').slice(0, 10);
    }

    trimByUnderscor(string) {
        return string.replace(/^_+|_+$/g, '');
    }

    getRandomFromFromArray(arr, count) {
        let shuffled = [...arr];
        for (let i = shuffled.length - 1; i > 0; i--) {
            const randomIndex = Math.floor(Math.random() * (i + 1));
            [shuffled[i], shuffled[randomIndex]] = [shuffled[randomIndex], shuffled[i]];
        }
        return shuffled.slice(0, count);
    }

    wordToDigit(word) {
        const map = { 'ONE': 1, 'TWO': 2, 'THREE': 3, 'FOUR': 4, 'FIVE': 5, 'SIX': 6 };
        return map[word.toUpperCase()];
    };

    shuffleObject(obj) {
        let newObj = Object.entries(obj);
        for (let i = newObj.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [newObj[i], newObj[j]] = [newObj[j], newObj[i]];
        }
        return Object.fromEntries(newObj);
    };

    shuffleArray(arr) {
        for (let i = arr.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [arr[i], arr[j]] = [arr[j], arr[i]]; // Swap elements
        }
        return arr;
    };
}

module.exports = new UtilHelper();