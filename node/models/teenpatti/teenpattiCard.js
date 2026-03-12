module.exports = (sequelize, DataTypes) => {
    const TeenpattiCard = sequelize.define("tbl_game_card", {
        id: {
            type: DataTypes.INTEGER,
            primaryKey: true,
            autoIncrement: true
        },
        game_id: {
            type: DataTypes.INTEGER,
        },
        user_id: {
            type: DataTypes.INTEGER,
        },
        card1: {
            type: DataTypes.STRING,
        },
        card2: {
            type: DataTypes.STRING,
        },
        card3: {
            type: DataTypes.STRING,
        },
        packed: {
            type: DataTypes.TINYINT,
            comment: "1=packed"
        },
        seen: {
            type: DataTypes.TINYINT,
            comment: "1=seen"
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
        tableName: 'tbl_game_card',
        timestamps: true,
        createdAt: 'added_date',
        updatedAt: 'updated_date',
        indexes: [
            {
                name: 'idx_game_id',
                fields: ['game_id']
            }
        ]
    });

    TeenpattiCard.associate = (models) => {
        TeenpattiCard.belongsTo(models.user, { foreignKey: 'user_id', as: 'user' });
    };

    return TeenpattiCard;
}