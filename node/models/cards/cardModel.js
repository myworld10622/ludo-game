module.exports = (sequelize, DataTypes) => {
    const Card = sequelize.define("tbl_cards", {
        id: {
            type: DataTypes.INTEGER,
            primaryKey: true,
            autoIncrement: true
        },
        cards: {
            type: DataTypes.STRING,
        },
    }, {
        tableName: 'tbl_cards',
        timestamps: false,
    })

    return Card;
}