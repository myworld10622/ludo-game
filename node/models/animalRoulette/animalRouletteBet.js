module.exports = (sequelize, DataTypes) => {
    const AnimalRouletteBet = sequelize.define("tbl_animal_roulette_bet", {
        id: {
            type: DataTypes.INTEGER,
            primaryKey: true,
            autoIncrement: true
        },
        animal_roulette_id: {
            type: DataTypes.INTEGER,
        },
        user_id: {
            type: DataTypes.INTEGER,
        },
        bet: {
            type: DataTypes.TINYINT,
        },
        amount: {
            type: DataTypes.INTEGER,
        },
        winning_amount: {
            type: DataTypes.FLOAT,
        },
        user_amount: {
            type: DataTypes.FLOAT
        },
        comission_amount: {
            type: DataTypes.FLOAT
        },
        minus_unutilized_wallet: {
            type: DataTypes.FLOAT,
            defaultValue: 0
        },
        minus_winning_wallet: {
            type: DataTypes.FLOAT,
            defaultValue: 0
        },
        minus_bonus_wallet: {
            type: DataTypes.FLOAT,
            defaultValue: 0
        },
        added_date: {
            type: DataTypes.DATE,
            allowNull: false,
            defaultValue: DataTypes.NOW
        }
    }, {
        tableName: 'tbl_animal_roulette_bet',
        timestamps: true,
        createdAt: 'added_date',
        updatedAt: false
    })

    AnimalRouletteBet.associate = (models) => {
        AnimalRouletteBet.belongsTo(models.AnimalRoulette, { foreignKey: 'animal_roulette_id', as: 'animal' });
    };

    return AnimalRouletteBet;

}