module.exports = (sequelize, DataTypes) => {
    const CardRummy = sequelize.define("tbl_cards_rummy", {
        id: {
            type: DataTypes.INTEGER,
            primaryKey: true,
            autoIncrement: true
        },
        cards: {
            type: DataTypes.STRING,
        },
    }, {
        tableName: 'tbl_cards_rummy',
        timestamps: false,
    })

    return CardRummy;
}