import { useState, useEffect } from 'react'
import { useParams } from 'react-router-dom'
import './App.css'

function App() {
    const [showDetails, setShowDetails] = useState(null)
    const [expandedCourse, setExpandedCourse] = useState(null)
    const { studentId } = useParams() // Récupération de l'ID depuis l'URL
    const [studentInfo, setStudentInfo] = useState(null)
    const [courses, setCourses] = useState([])
    const [loading, setLoading] = useState(true)

    useEffect(() => {
        // Récupérer les informations de l'étudiant et les cours depuis l'API Symfony
        if (studentId) {
            console.log('ID de l\'étudiant connecté:', studentId)

            // Récupérer les informations de l'étudiant
            fetch(`http://127.0.0.1:8000/api/students/${studentId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Erreur lors de la récupération des données')
                    }
                    return response.json()
                })
                .then(data => {
                    console.log('Données étudiant:', data)
                    setStudentInfo(data)
                })
                .catch(error => {
                    console.error('Erreur:', error)
                })

            // Récupérer tous les cours
            fetch(`http://127.0.0.1:8000/api/courses`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Erreur lors de la récupération des cours')
                    }
                    return response.json()
                })
                .then(data => {
                    console.log('Cours:', data)
                    setCourses(data)
                    setLoading(false)
                })
                .catch(error => {
                    console.error('Erreur:', error)
                    setLoading(false)
                })
        } else {
            setLoading(false)
        }
    }, [studentId])

    const handleLogout = () => {
        localStorage.removeItem('token'); // si vous utilisez un token

        // Redirection vers le backend Symfony
        window.location.href = 'http://127.0.0.1:8000';
    };

    const toggleCourse = (courseId) => {
        setExpandedCourse(expandedCourse === courseId ? null : courseId)
    }

    const toggleDetails = (id) => {
        setShowDetails(showDetails === id ? null : id)
    }

    const downloadDocument = (downloadUrl, documentTitle) => {
        // Utiliser l'URL complète fournie par l'API
        window.open(`http://127.0.0.1:8000${downloadUrl}`, '_blank')
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
            <div className="hero">
                <div className="hero-content">
                    <h1>
                        Bienvenue, {loading ? 'Chargement...' :
                            studentInfo ? `${studentInfo.first_name} ${studentInfo.last_name}` :
                            'Étudiant'}
                    </h1>
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
                {/* Tous les Cours */}
                <div className="carousel-section">
                    <div className="carousel-header">
                        <h2>📚 Tous les Cours Disponibles</h2>
                    </div>

                    {loading ? (
                        <div style={{ textAlign: 'center', padding: '2rem' }}>Chargement des cours...</div>
                    ) : courses.length === 0 ? (
                        <div style={{ textAlign: 'center', padding: '2rem' }}>Aucun cours disponible pour le moment</div>
                    ) : (
                        <div className="carousel">
                            {courses.map(course => (
                                <div key={course.id} style={{ width: '100%', marginBottom: '1rem' }}>
                                    <div className="carousel-item" style={{ cursor: 'pointer' }} onClick={() => toggleCourse(course.id)}>
                                        <div className="carousel-item-document">📖</div>
                                        <div className="carousel-item-content">
                                            <div className="carousel-item-title">{course.title}</div>
                                            <div className="carousel-item-meta">
                                                👨‍🏫 {course.teacher?.first_name} {course.teacher?.last_name}
                                                {' • '}
                                                📹 {course.videos?.length || 0} vidéo(s)
                                                {' • '}
                                                📄 {course.documents?.length || 0} document(s)
                                            </div>
                                            <button className="btn-take-qcm" onClick={(e) => { e.stopPropagation(); toggleCourse(course.id); }}>
                                                {expandedCourse === course.id ? '🔼 Masquer' : '🔽 Voir le contenu'}
                                            </button>
                                        </div>
                                    </div>

                                    {/* Contenu du cours expandé */}
                                    {expandedCourse === course.id && (
                                        <div style={{
                                            background: '#f8f9fa',
                                            padding: '1.5rem',
                                            borderRadius: '8px',
                                            marginTop: '0.5rem',
                                            border: '1px solid #dee2e6'
                                        }}>
                                            {/* Description du cours */}
                                            <div style={{ marginBottom: '1.5rem' }}>
                                                <h3 style={{ fontSize: '1.2rem', marginBottom: '0.5rem', color: '#495057' }}>📝 Description</h3>
                                                <p style={{ color: '#6c757d' }}>{course.contenu}</p>
                                            </div>

                                            {/* Vidéos */}
                                            {course.videos && course.videos.length > 0 && (
                                                <div style={{ marginBottom: '1.5rem' }}>
                                                    <h3 style={{ fontSize: '1.2rem', marginBottom: '0.5rem', color: '#495057' }}>📹 Vidéos ({course.videos.length})</h3>
                                                    <div style={{ display: 'grid', gap: '0.75rem' }}>
                                                        {course.videos.map(video => (
                                                            <div key={video.id} style={{
                                                                background: 'white',
                                                                padding: '1rem',
                                                                borderRadius: '6px',
                                                                display: 'flex',
                                                                justifyContent: 'space-between',
                                                                alignItems: 'center',
                                                                border: '1px solid #dee2e6'
                                                            }}>
                                                                <div>
                                                                    <div style={{ fontWeight: '500', marginBottom: '0.25rem' }}>🎬 {video.title}</div>
                                                                    <div style={{ fontSize: '0.875rem', color: '#6c757d' }}>
                                                                        ⏱️ {video.duration} minutes
                                                                    </div>
                                                                </div>
                                                                <a
                                                                    href={video.url}
                                                                    target="_blank"
                                                                    rel="noopener noreferrer"
                                                                    className="btn btn-outline-small"
                                                                    style={{ textDecoration: 'none' }}
                                                                >
                                                                    ▶️ Regarder
                                                                </a>
                                                            </div>
                                                        ))}
                                                    </div>
                                                </div>
                                            )}

                                            {/* Documents */}
                                            {course.documents && course.documents.length > 0 && (
                                                <div style={{ marginBottom: '1.5rem' }}>
                                                    <h3 style={{ fontSize: '1.2rem', marginBottom: '0.5rem', color: '#495057' }}>📄 Documents ({course.documents.length})</h3>
                                                    <div style={{ display: 'grid', gap: '0.75rem' }}>
                                                        {course.documents.map(document => (
                                                            <div key={document.id} style={{
                                                                background: 'white',
                                                                padding: '1rem',
                                                                borderRadius: '6px',
                                                                display: 'flex',
                                                                justifyContent: 'space-between',
                                                                alignItems: 'center',
                                                                border: '1px solid #dee2e6'
                                                            }}>
                                                                <div>
                                                                    <div style={{ fontWeight: '500' }}>📑 {document.title}</div>
                                                                </div>
                                                                <button
                                                                    onClick={() => downloadDocument(document.download_url, document.title)}
                                                                    className="btn btn-outline-small"
                                                                >
                                                                    ⬇️ Télécharger
                                                                </button>
                                                            </div>
                                                        ))}
                                                    </div>
                                                </div>
                                            )}

                                            {/* Bouton QCM */}
                                            <div style={{ marginTop: '1.5rem', textAlign: 'center', padding: '1rem', background: 'white', borderRadius: '8px', border: '2px solid #007bff' }}>
                                                <button
                                                    className="btn-take-qcm"
                                                    onClick={() => takeQCM(course.title)}
                                                    style={{ width: 'auto', padding: '0.75rem 2rem', fontSize: '1.1rem', fontWeight: 'bold' }}
                                                >
                                                    ✏️ Passer le QCM sur ce cours
                                                </button>
                                            </div>
                                        </div>
                                    )}
                                </div>
                            ))}
                        </div>
                    )}
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
