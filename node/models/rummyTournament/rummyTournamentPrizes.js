module.exports = (sequelize, DataTypes) => {
    const RummyTournamentPrizes = sequelize.define("tbl_rummy_tournament_prizes", {
        id: {
            type: DataTypes.INTEGER,
            primaryKey: true,
            autoIncrement: true
        },
        tournament_id: {
            type: DataTypes.INTEGER,
        },
        from_position: {
            type: DataTypes.INTEGER,
        },
        to_position: {
            type: DataTypes.INTEGER,
        },
        players: {
            type: DataTypes.STRING
        },
        winning_price: {
            type: DataTypes.STRING,
        },
        given_in_round: {
            type: DataTypes.INTEGER,
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
        },
        isDeleted: {
            type: DataTypes.TINYINT,
            allowNull: false,
            defaultValue: 0
        }
    }, {
        tableName: 'tbl_rummy_tournament_prizes',
        timestamps: true,
        createdAt: 'added_date',
        updatedAt: 'updated_date',
        defaultScope: {
            where: {
                isDeleted: 0
            }
        },
        scopes: {
            withDeleted: {
                where: {}
            }
        },
        indexes: [
            {
                name: 'idx_isDeleted',
                fields: ['isDeleted']
            }
        ]
    });

    return RummyTournamentPrizes;
}