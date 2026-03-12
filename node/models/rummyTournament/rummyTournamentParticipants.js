module.exports = (sequelize, DataTypes) => {
    const RummyTournamentCardDrop = sequelize.define("tbl_rummy_tournament_participants", {
        id: {
            type: DataTypes.INTEGER,
            primaryKey: true,
            autoIncrement: true
        },
        tournament_id: {
            type: DataTypes.INTEGER,
            allowNull: false
        },
        user_id: {
            type: DataTypes.INTEGER,
            allowNull: false
        },
        amount: {
            type: DataTypes.INTEGER,
            allowNull: false
        },
        join_type: {
            type: DataTypes.TINYINT,
            defaultValue: 0,
            comment: "0 = Fee 1 = Entry Pass"
        },
        total_points: {
            type: DataTypes.INTEGER,
            defaultValue: 0
        },
        round: {
            type: DataTypes.INTEGER,
            defaultValue: 0
        },
        can_tournament_play: {
            type: DataTypes.TINYINT,
            defaultValue: 1,
            comment: "1 = Yes, 0 = No"
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
        tableName: 'tbl_rummy_tournament_participants',
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
                name: 'idx_tournament_id',
                fields: ['tournament_id']
            },
            {
                name: 'idx_isDeleted',
                fields: ['isDeleted']
            }
        ]
    })

    return RummyTournamentCardDrop;
}