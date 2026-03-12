/*const HIGH_CARDS = [
    // Triple
    { card1: "BPA", card2: "RPA", card3: "BLA" },
    { card1: "BP7", card2: "RP7", card3: "BL7" },
    // Pure
    { card1: "BPA", card2: "BPK", card3: "BPQ" },
    { card1: "RSJ", card2: "RS9", card3: "RS10" },
    { card1: "RP5", card2: "RP6", card3: "RP7" },
    // Set
    { card1: "BPK", card2: "RSJ", card3: "RSQ" },
    { card1: "RS6", card2: "RS8", card3: "BP7" },
    { card1: "BP3", card2: "RS5", card3: "BL4" },
    { card1: "RP8", card2: "RS7", card3: "BL9" },
    // Color
    { card1: "BPA", card2: "BP10", card3: "BP6" },
    { card1: "RSQ", card2: "RS9", card3: "RS2" },
    { card1: "RSA", card2: "RS8", card3: "RS9" },
    { card1: "RPK", card2: "RP7", card3: "RP3" },
    // Jodi
    { card1: "BPA", card2: "RSA", card3: "BL9" },
    { card1: "BP10", card2: "RP8", card3: "RS10" },
    { card1: "BPK", card2: "RPK", card3: "RS5" },
    { card1: "BPA", card2: "BL8", card3: "RS8" },
    // High Card
    { card1: "BPA", card2: "RSQ", card3: "BP3" },
    { card1: "RPK", card2: "RSQ", card3: "BP7" },
    { card1: "BPA", card2: "RS10", card3: "BP6" },
];*/
const HIGH_CARDS = [
    { card1: "BPA", card2: "RS8", card3: "BL3" },
    { card1: "BPK", card2: "RS7", card3: "BL4" },
    { card1: "BP9", card2: "RS7", card3: "BL2" },
    { card1: "BP10", card2: "RSA", card3: "BL2" },
    { card1: "BP9", card2: "RS5", card3: "BL6" },
    { card1: "BP3", card2: "RS2", card3: "BL8" },
    { card1: "BP4", card2: "RS5", card3: "BL9" },
    { card1: "BP3", card2: "RS5", card3: "BL6" },
    { card1: "BPQ", card2: "RSK", card3: "BL8" },
    { card1: "BP4", card2: "RS6", card3: "BL9" },
    { card1: "BPA", card2: "RS6", card3: "BP3" },
    { card1: "RPK", card2: "RS10", card3: "BP7" },
    { card1: "BPA", card2: "RS5", card3: "BP6" },
    { card1: "BP7", card2: "RSA", card3: "BL9" },
    { card1: "RP9", card2: "BLQ", card3: "BP5" },
    { card1: "BLA", card2: "RS8", card3: "BL3" },
    { card1: "BLK", card2: "RS4", card3: "RSQ" },
    { card1: "BP8", card2: "RSA", card3: "BL5" },
    { card1: "RP7", card2: "BL10", card3: "BPK" },
    { card1: "BL9", card2: "RSJ", card3: "RPK" }
]

const LOW_CARDS = [
    // 5 High
    { card1: "BP5", card2: "RS3", card3: "BL2" },
    { card1: "RS5", card2: "BP4", card3: "BP2" },

    // 6 High
    { card1: "RP6", card2: "RP3", card3: "RS2" },
    { card1: "BL6", card2: "RS3", card3: "RP5" },

    // 7 High
    { card1: "BL7", card2: "BP3", card3: "RS2" },
    { card1: "RP7", card2: "RS4", card3: "BP3" },
    { card1: "BP7", card2: "RP3", card3: "BL5" },
    { card1: "BP7", card2: "RP5", card3: "RS4" },
    { card1: "RS7", card2: "BL2", card3: "RP6" },

    // 8 High
    { card1: "RP8", card2: "BL4", card3: "RP2" },
    { card1: "BL8", card2: "RS5", card3: "BP3" },
    { card1: "RS8", card2: "RP5", card3: "BL4" },
    { card1: "RP8", card2: "BP6", card3: "RP5" },

    // 9 High
    { card1: "RS9", card2: "RP5", card3: "BP3" },
    { card1: "RP9", card2: "RS6", card3: "BP4" },
    { card1: "BP9", card2: "RP8", card3: "RS4" },

    // 10 High
    { card1: "BP10", card2: "RS5", card3: "RP2" },
    { card1: "RS10", card2: "BL5", card3: "RS3" },
    { card1: "RP10", card2: "RS2", card3: "BL9" },

    // J High
    { card1: "RPJ", card2: "RS4", card3: "BP2" },
    { card1: "BPJ", card2: "RS2", card3: "BL7" },
];

module.exports = {
    HIGH_CARDS
}