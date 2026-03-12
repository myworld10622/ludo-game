module.exports = (sequelize, DataTypes) => {
    const Jackpot = sequelize.define("tbl_jackpot", {
        id: {
            type: DataTypes.INTEGER,
            primaryKey: true,
            autoIncrement: true
        },
        room_id: {
            type: DataTypes.INTEGER,
        },
        card1: {
            type: DataTypes.STRING(5),
        },
        card2: {
            type: DataTypes.STRING(5),
        },
        card3: {
            type: DataTypes.STRING(5),
        },
        winning: {
            type: DataTypes.TINYINT,
        },
        status: {
            type: DataTypes.TINYINT,
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
        total_amount: {
            type: DataTypes.FLOAT
        },
        random_amount: {
            type: DataTypes.INTEGER,
            defaultValue: 0
        },
        admin_profit: {
            type: DataTypes.FLOAT
        },
        end_datetime: {
            type: DataTypes.DATE
        },
        random: {
            type: DataTypes.TINYINT
        },
        added_date: {
            type: DataTypes.DATE,
            allowNull: false,
            defaultValue: DataTypes.NOW
        },
        updated_date: {
            type: DataTypes.DATE,
            allowNull: false,
            defaultValue: DataTypes.NOW
        }
    }, {
        tableName: 'tbl_jackpot',
        timestamps: true,
        createdAt: 'added_date',
        updatedAt: 'updated_date'
    })
    Jackpot.associate = (models) => {
        Jackpot.hasMany(models.JackpotBet, { foreignKey: 'jackpot_id', as: 'bets' });
    };

    return Jackpot;
}