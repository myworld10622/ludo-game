module.exports = (sequelize, DataTypes) => {
    const Bacarrat = sequelize.define("tbl_baccarat", {
        id: {
            type: DataTypes.INTEGER,
            primaryKey: true,
            autoIncrement: true
        },
        room_id: {
            type: DataTypes.INTEGER,
        },
        main_card: {
            type: DataTypes.STRING(5),
        },
        winning: {
            type: DataTypes.TINYINT,
            comment: "0=player,1=banker"
        },
        player_pair: {
            type: DataTypes.TINYINT,
        },
        banker_pair: {
            type: DataTypes.TINYINT,
        },
        status: {
            type: DataTypes.TINYINT,
            comment: "0=started,1=ended"
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
        admin_profit: {
            type: DataTypes.FLOAT
        },
        random_amount: {
            type: DataTypes.INTEGER,
            defaultValue: 0
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
        tableName: 'tbl_baccarat',
        timestamps: true,
        createdAt: 'added_date',
        updatedAt: 'updated_date'
    })
    Bacarrat.associate = (models) => {
        Bacarrat.hasMany(models.BacarratBet, { foreignKey: 'baccarat_id', as: 'bets' });
    };

    return Bacarrat;
}