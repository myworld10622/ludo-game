module.exports = (sequelize, DataTypes) => {
    const RummyTournamentCard = sequelize.define("tbl_rummy_tournament_card", {
        id: {
            type: DataTypes.INTEGER,
            primaryKey: true,
            autoIncrement: true
        },
        game_id: {
            type: DataTypes.INTEGER,
            allowNull: false,
        },
        user_id: {
            type: DataTypes.INTEGER,
            allowNull: false,
        },
        card: {
            type: DataTypes.STRING,
            allowNull: false,
        },
        packed: {
            type: DataTypes.TINYINT,
            defaultValue: 0,
            allowNull: false,
        },
        is_drop_card: {
            type: DataTypes.TINYINT,
            defaultValue: 0,
            allowNull: false,
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
        tableName: 'tbl_rummy_tournament_card',
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
                name: 'idx_game_id',
                fields: ['game_id']
            },
            {
                name: 'idx_isDeleted',
                fields: ['isDeleted']
            }
        ]
    });

    RummyTournamentCard.associate = (models) => {
        RummyTournamentCard.belongsTo(models.user, { foreignKey: 'user_id' });
    };

    return RummyTournamentCard;
}