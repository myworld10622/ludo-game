module.exports = (sequelize, DataTypes) => {
    const RouletteTempBet = sequelize.define("tbl_roulette_temp_bet", {
        id: {
            type: DataTypes.INTEGER,
            primaryKey: true,
            autoIncrement: true
        },
        roulette_id: {
            type: DataTypes.INTEGER,
        },
        bet: {
            type: DataTypes.TINYINT,
        },
        amount: {
            type: DataTypes.DOUBLE,
        },
        added_date: {
            type: DataTypes.DATE,
            allowNull: false,
            defaultValue: DataTypes.NOW
        }
    }, {
        tableName: 'tbl_roulette_temp_bet',
        timestamps: true,
        createdAt: 'added_date',
        updatedAt: false
    })

    return RouletteTempBet;
}