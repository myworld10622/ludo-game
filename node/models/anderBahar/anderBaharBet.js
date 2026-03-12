module.exports = (sequelize, DataTypes) => {
    const AnderBaharBet = sequelize.define("tbl_ander_baher_bet", {
        id: {
            type: DataTypes.INTEGER,
            primaryKey: true,
            autoIncrement: true
        },
        ander_baher_id: {
            type: DataTypes.INTEGER,
        },
        user_id: {
            type: DataTypes.INTEGER,
        },
        bet: {
            type: DataTypes.TINYINT,
            comment: "0=Andar,1=Bahar"
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
        tableName: 'tbl_ander_baher_bet',
        timestamps: true,
        createdAt: 'added_date',
        updatedAt: false,
        indexes: [
            {
                name: 'idx_ander_baher_id',
                fields: ['ander_baher_id']
            },
            {
                name: 'idx_ander_bahar_bet',
                fields: ['ander_baher_id', 'bet']
            }
        ]
    });

    AnderBaharBet.associate = (models) => {
        AnderBaharBet.belongsTo(models.AnderBahar, { foreignKey: 'ander_baher_id', as: 'andar_bahar' });
    };

    return AnderBaharBet;

}