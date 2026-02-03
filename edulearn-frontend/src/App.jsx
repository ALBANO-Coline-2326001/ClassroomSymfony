import { useState } from 'react'
import './App.css'

function App() {
    const [showDetails, setShowDetails] = useState(null)

    const handleLogout = () => {
        localStorage.removeItem('token'); // si vous utilisez un token

        // Redirection vers le backend Symfony
        window.location.href = 'http://127.0.0.1:8000';
    };


    const videos = [
        { id: 1, title: 'Introduction à Symfony', teacher: 'Prof. Martin', duration: '45 min' },
        { id: 2, title: 'Security Bundle', teacher: 'Prof. Dubois', duration: '60 min' },
        { id: 3, title: 'API Platform', teacher: 'Prof. Bernard', duration: '50 min' },
        { id: 4, title: 'Doctrine ORM', teacher: 'Prof. Laurent', duration: '55 min' },
        { id: 5, title: 'Twig Templates', teacher: 'Prof. Sophie', duration: '40 min' }
    ]

    const documents = [
        { id: 1, title: 'Guide Symfony 7', teacher: 'Prof. Martin', pages: '25 pages' },
        { id: 2, title: 'Architecture MVC', teacher: 'Prof. Dubois', pages: '18 pages' },
        { id: 3, title: 'REST API Best Practices', teacher: 'Prof. Bernard', pages: '30 pages' },
        { id: 4, title: 'Database Design', teacher: 'Prof. Laurent', pages: '22 pages' },
        { id: 5, title: 'Frontend avec Twig', teacher: 'Prof. Sophie', pages: '15 pages' }
    ]

    const toggleDetails = (id) => {
        setShowDetails(showDetails === id ? null : id)
    }

    const takeQCM = (title) => {
        alert(`🎓 Démarrage du QCM : ${title}\n\n✓ 10 questions\n✓ Durée : 20 minutes\n✓ Note sur 20\n\nBonne chance ! 🍀`)
    }

    return (
        <>
            {/* Navigation */}
            <nav className="navbar">
                <div className="nav-container">
                    <div className="logo">🎓 EduLearn</div>
                    <div className="nav-buttons">
                        <button className="btn btn-outline">Les Cours</button>
                        <button onClick={handleLogout} className="btn-danger">Déconnexion </button>

                    </div>
                </div>
            </nav>

            {/* Hero Section */}
            {/* Correction : Le hero prend 100% de la largeur, mais le contenu interne est centré */}
            <div className="hero">
                <div className="hero-content">
                    <h1>Bienvenue, Étudiant</h1>
                    <p>Accédez à vos cours, documents et QCM</p>
                    <div className="stats">
                        <div className="stat-item">
                            <div className="stat-number">12</div>
                            <div className="stat-label">Cours actifs</div>
                        </div>
                        <div className="stat-item">
                            <div className="stat-number">8</div>
                            <div className="stat-label">QCM disponibles</div>
                        </div>
                        <div className="stat-item">
                            <div className="stat-number">15.5/20</div>
                            <div className="stat-label">Moyenne générale</div>
                        </div>
                    </div>
                </div>
            </div>

            <div className="container">
                {/* Vidéos */}
                <div className="carousel-section">
                    <div className="carousel-header">
                        <h2>📹 Vidéos de Cours</h2>
                    </div>
                    <div className="carousel">
                        {videos.map(video => (
                            <div key={video.id} className="carousel-item">
                                <div className="carousel-item-video">
                                    🎬
                                    <div className="play-btn">▶</div>
                                </div>
                                <div className="carousel-item-content">
                                    <div className="carousel-item-title">{video.title}</div>
                                    <div className="carousel-item-meta">👨‍🏫 {video.teacher} • ⏱️ {video.duration}</div>
                                    <button className="btn-take-qcm" onClick={() => takeQCM(video.title)}>
                                        ✏️ Passer le QCM
                                    </button>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>

                {/* Documents */}
                <div className="carousel-section">
                    <div className="carousel-header">
                        <h2>📄 Documents de Cours</h2>
                    </div>
                    <div className="carousel">
                        {documents.map(doc => (
                            <div key={doc.id} className="carousel-item">
                                <div className="carousel-item-document">📑</div>
                                <div className="carousel-item-content">
                                    <div className="carousel-item-title">{doc.title}</div>
                                    <div className="carousel-item-meta">👨‍🏫 {doc.teacher} • 📄 {doc.pages}</div>
                                    <button className="btn-take-qcm" onClick={() => takeQCM(doc.title)}>
                                        ✏️ Passer le QCM
                                    </button>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>

                {/* Mes Résultats */}
                <div className="results-section">
                    <div className="results-header">
                        <h2>📊 Mes Résultats</h2>
                    </div>
                    <table className="results-table">
                        <thead>
                        <tr>
                            <th>QCM</th>
                            <th>Date</th>
                            <th>Score</th>
                            <th>Note</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td><strong>Introduction à Symfony</strong></td>
                            <td>15/01/2026</td>
                            <td>18/20</td>
                            <td><span className="score-badge score-excellent">Excellent</span></td>
                            <td><button className="btn btn-outline-small" onClick={() => toggleDetails(1)}>Voir détails</button></td>
                        </tr>
                        {showDetails === 1 && (
                            <tr>
                                <td colSpan="5" className="details-cell">
                                    <div className="details-row">
                                        <div className="details-grid">
                                            <div className="detail-item">
                                                <div className="detail-label">📝 Questions</div>
                                                <div className="detail-value">10</div>
                                            </div>
                                            <div className="detail-item">
                                                <div className="detail-label">✅ Correctes</div>
                                                <div className="detail-value">9</div>
                                            </div>
                                            <div className="detail-item">
                                                <div className="detail-label">⏱️ Temps</div>
                                                <div className="detail-value">18 min</div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        )}
                        <tr>
                            <td><strong>Security Bundle</strong></td>
                            <td>14/01/2026</td>
                            <td>15/20</td>
                            <td><span className="score-badge score-good">Bien</span></td>
                            <td><button className="btn btn-outline-small" onClick={() => toggleDetails(2)}>Voir détails</button></td>
                        </tr>
                        <tr>
                            <td><strong>API Platform</strong></td>
                            <td>13/01/2026</td>
                            <td>12/20</td>
                            <td><span className="score-badge score-average">Moyen</span></td>
                            <td><button className="btn btn-outline-small" onClick={() => toggleDetails(3)}>Voir détails</button></td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </>
    )
}

export default App
