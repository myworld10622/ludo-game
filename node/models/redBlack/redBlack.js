module.exports = (sequelize, DataTypes) => {
    const RedBlack = sequelize.define("tbl_red_black", {
        id: {
            type: DataTypes.INTEGER,
            primaryKey: true,
            autoIncrement: true
        },
        room_id: {
            type: DataTypes.INTEGER,
        },
        main_card: {
            type: DataTypes.STRING,
        },
        winning: {
            type: DataTypes.TINYINT,
        },
        winning_rule: {
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
        tableName: 'tbl_red_black',
        timestamps: true,
        createdAt: 'added_date',
        updatedAt: 'updated_date'
    })
    RedBlack.associate = (models) => {
        RedBlack.hasMany(models.RedBlackBet, { foreignKey: 'red_black_id', as: 'bets' });
    };

    return RedBlack;
}