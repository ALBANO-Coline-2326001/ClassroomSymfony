import React, { useState, useEffect } from 'react'
import { useParams } from 'react-router-dom'
import './App.css'

function App() {
    // --- États pour les données ---
    const [showDetails, setShowDetails] = useState(null)
    const [expandedCourse, setExpandedCourse] = useState(null)
    const { studentId } = useParams()
    const [studentInfo, setStudentInfo] = useState(null)
    const [courses, setCourses] = useState([])
    const [qcms, setQcms] = useState([])
    const [dbResults, setDbResults] = useState([]) // Notes réelles de la BD
    const [loading, setLoading] = useState(true)

    // --- États pour la Popup QCM ---
    const [activeQcm, setActiveQcm] = useState(null)
    const [currentQuestionIndex, setCurrentQuestionIndex] = useState(0)
    const [score, setScore] = useState(0)
    const [showResult, setShowResult] = useState(false)

    // 1. Charger les notes réelles depuis la BD
    const fetchStudentResults = () => {
        if (studentId) {
            fetch(`http://127.0.0.1:8000/api/students/${studentId}/qcm-results`)
                .then(res => res.json())
                .then(data => setDbResults(data))
                .catch(err => console.error("Erreur chargement résultats:", err))
        }
    }

    useEffect(() => {
        if (studentId) {
            // Informations étudiant
            fetch(`http://127.0.0.1:8000/api/students/${studentId}`)
                .then(res => res.json())
                .then(data => setStudentInfo(data))

            // Liste des cours
            fetch(`http://127.0.0.1:8000/api/courses`)
                .then(res => res.json())
                .then(data => {
                    setCourses(data)
                    setLoading(false)
                })

            // Notes existantes
            fetchStudentResults()
        }
    }, [studentId])

    useEffect(() => {
        // Liste des QCM disponibles
        fetch(`http://127.0.0.1:8000/api/qcms`)
            .then(res => res.json())
            .then(data => setQcms(data))
    }, [])

    // --- Logique du QCM ---
    const startQcm = (qcmId) => {
        fetch(`http://127.0.0.1:8000/api/qcms/${qcmId}`)
            .then(res => res.json())
            .then(data => {
                setActiveQcm(data)
                setCurrentQuestionIndex(0)
                setScore(0)
                setShowResult(false)
            })
    }

    const handleAnswerSelection = (isCorrect) => {
        const newScore = isCorrect ? score + 1 : score
        if (isCorrect) setScore(newScore)

        const nextQuestion = currentQuestionIndex + 1
        if (nextQuestion < activeQcm.questions.length) {
            setCurrentQuestionIndex(nextQuestion)
        } else {
            setShowResult(true)
            submitScore(newScore) // Enregistrement automatique
        }
    }

    // Fonction dédiée pour recharger uniquement les résultats
    const refreshResults = () => {
        if (studentId) {
            console.log("Rafraîchissement des résultats pour l'étudiant", studentId);
            fetch(`http://127.0.0.1:8000/api/students/${studentId}/qcm-results`)
                .then(res => res.json())
                .then(data => {
                    console.log("Nouveaux résultats reçus:", data);
                    setDbResults(data);
                })
                .catch(err => console.error("Erreur refresh:", err));
        }
    };

    const submitScore = (finalScore) => {
        console.log(`Envoi du score: ${finalScore} pour QCM ID: ${activeQcm.id}`);

        fetch(`http://127.0.0.1:8000/api/students/${studentId}/qcms/${activeQcm.id}/submit`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ score: finalScore })
        })
            .then(response => {
                if (!response.ok) throw new Error('Erreur réseau lors de la sauvegarde');
                return response.json();
            })
            .then(data => {
                console.log("Sauvegarde réussie:", data);
                refreshResults(); // Appel immédiat pour mettre à jour le tableau
            })
            .catch(err => {
                console.error("Erreur sauvegarde:", err);
                alert("Erreur lors de l'enregistrement du score. Vérifiez la console.");
            });
    }

    // --- Fonctions utilitaires ---
    const closePopup = () => setActiveQcm(null)
    const handleLogout = () => { localStorage.removeItem('token'); window.location.href = 'http://127.0.0.1:8000' }
    const toggleCourse = (id) => setExpandedCourse(expandedCourse === id ? null : id)
    const toggleDetails = (id) => setShowDetails(showDetails === id ? null : id)
    const downloadDocument = (url) => window.open(`http://127.0.0.1:8000${url}`, '_blank')

    return (
        <>
            {/* Navigation */}
            <nav className="navbar">
                <div className="nav-container">
                    <div className="logo">🎓 EduLearn</div>
                    <div className="nav-buttons">
                        <button className="btn btn-outline">Les Cours</button>
                        <button onClick={handleLogout} className="btn-danger">Déconnexion</button>
                    </div>
                </div>
            </nav>

            {/* Hero Section */}
            <div className="hero">
                <div className="hero-content">
                    <h1>Bienvenue, {studentInfo ? `${studentInfo.first_name} ${studentInfo.last_name}` : 'Chargement...'}</h1>
                    <p>Accédez à vos cours, documents et QCM</p>
                    <div className="stats">
                        <div className="stat-item"><div className="stat-number">{courses.length}</div><div className="stat-label">Cours actifs</div></div>
                        <div className="stat-item"><div className="stat-number">{dbResults.length}</div><div className="stat-label">QCM Faits</div></div>
                    </div>
                </div>
            </div>

            <div className="container">
                {/* 1. SECTION TOUS LES COURS (Vidéos + Documents) */}
                <div className="carousel-section">
                    <div className="carousel-header"><h2>📚 Tous les Cours Disponibles</h2></div>
                    {loading ? <p>Chargement...</p> : (
                        <div className="carousel">
                            {courses.map(course => (
                                <div key={course.id} style={{ width: '100%', marginBottom: '1rem' }}>
                                    <div className="carousel-item" style={{ cursor: 'pointer' }} onClick={() => toggleCourse(course.id)}>
                                        <div className="carousel-item-document">📖</div>
                                        <div className="carousel-item-content">
                                            <div className="carousel-item-title">{course.title}</div>
                                            <div className="carousel-item-meta">
                                                👨‍🏫 {course.teacher?.first_name} {course.teacher?.last_name}
                                                {' • '} 📹 {course.videos?.length || 0} vidéo(s)
                                                {' • '} 📄 {course.documents?.length || 0} document(s)
                                            </div>
                                            <button className="btn-take-qcm" onClick={(e) => { e.stopPropagation(); toggleCourse(course.id); }}>
                                                {expandedCourse === course.id ? '🔼 Masquer' : '🔽 Voir le contenu'}
                                            </button>
                                        </div>
                                    </div>

                                    {expandedCourse === course.id && (
                                        <div style={{ background: '#f8f9fa', padding: '1.5rem', borderRadius: '8px', marginTop: '0.5rem', border: '1px solid #dee2e6' }}>
                                            <h3 style={{ fontSize: '1.2rem' }}>📝 Description</h3>
                                            <p>{course.contenu}</p>

                                            {course.videos?.length > 0 && (
                                                <div style={{ marginTop: '1rem' }}>
                                                    <h4>📹 Vidéos</h4>
                                                    {course.videos.map(v => (
                                                        <div key={v.id} style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '5px' }}>
                                                            <span>🎬 {v.title}</span>
                                                            <a href={v.url} target="_blank" className="btn btn-outline-small">Regarder</a>
                                                        </div>
                                                    ))}
                                                </div>
                                            )}

                                            {course.documents?.length > 0 && (
                                                <div style={{ marginTop: '1rem' }}>
                                                    <h4>📄 Documents</h4>
                                                    {course.documents.map(d => (
                                                        <div key={d.id} style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '5px' }}>
                                                            <span>📑 {d.title}</span>
                                                            <button onClick={() => downloadDocument(d.download_url)} className="btn btn-outline-small">Télécharger</button>
                                                        </div>
                                                    ))}
                                                </div>
                                            )}
                                        </div>
                                    )}
                                </div>
                            ))}
                        </div>
                    )}
                </div>

                {/* 2. SECTION RÉSULTATS DYNAMIQUES */}
                <div className="results-section">
                    <div className="results-header"><h2>📊 Mes Résultats (Base de Données)</h2></div>
                    <table className="results-table">
                        <thead>
                        {/* Tout sur une ligne ou collé pour éviter les "whitespace nodes" */}
                        <tr>
                            <th>Cours</th>
                            <th>QCM</th>
                            <th>Date</th>
                            <th>Score</th>
                            <th>Note /20</th>
                            <th>Statut</th>
                        </tr>
                        </thead>
                        <tbody>
                        {dbResults.length === 0 ? (
                            <tr><td colSpan="6" style={{textAlign:'center', padding:'20px'}}>Aucun résultat enregistré.</td></tr>
                        ) : dbResults.map(res => {
                            const noteSur20 = res.total_questions > 0 ? ((res.score / res.total_questions) * 20).toFixed(1) : 0;

                            // CORRECTION ICI : Pas de <React.Fragment>, key sur le tr
                            return (
                                <tr key={res.id}>
                                    <td><span className="course-tag">📘 {res.course_title}</span></td>
                                    <td><strong>{res.qcm_title}</strong></td>
                                    <td>{res.date}</td>
                                    <td>{res.score} / {res.total_questions}</td>
                                    <td><strong>{noteSur20}/20</strong></td>
                                    <td>
                        <span className={`score-badge ${res.score >= res.total_questions / 2 ? 'score-excellent' : 'score-average'}`}>
                            {res.score >= res.total_questions / 2 ? 'Validé ✅' : 'Non validé ❌'}
                        </span>
                                    </td>
                                </tr>
                            );
                        })}
                        </tbody>
                    </table>
                </div>

                {/* 3. SECTION QCM DISPONIBLES */}
                <div className="qcm-section">
                    <div className="qcm-header"><h2>📋 QCM Disponibles</h2></div>
                    <div className="qcm-list">
                        {qcms.map(course => (
                            <div key={course.course_id} className="qcm-course">
                                <h3>{course.course_title}</h3>
                                <ul>{course.qcms.map(qcm => (
                                    <li key={qcm.id}>
                                        <span>{qcm.title} ({qcm.questions_count} questions)</span>
                                        <button className="btn-take-qcm" onClick={() => startQcm(qcm.id)}>🖊️ Passer le QCM</button>
                                    </li>
                                ))}</ul>
                            </div>
                        ))}
                    </div>
                </div>
            </div>

            {/* POPUP QCM */}
            {activeQcm && (
                <div className="qcm-popup-overlay">
                    <div className="qcm-popup-content">
                        <button className="qcm-popup-close" onClick={closePopup}>×</button>
                        {!showResult ? (
                            <>
                                <h2>{activeQcm.title}</h2>
                                <p className="qcm-step">Question {currentQuestionIndex + 1} / {activeQcm.questions.length}</p>
                                <h4 className="qcm-question">{activeQcm.questions[currentQuestionIndex].text}</h4>
                                <div className="qcm-answers">
                                    {activeQcm.questions[currentQuestionIndex].answers.map((ans) => (
                                        <button key={ans.id} className="qcm-answer-btn" onClick={() => handleAnswerSelection(ans.is_correct)}>{ans.text}</button>
                                    ))}
                                </div>
                            </>
                        ) : (
                            <div className="qcm-result-screen">
                                <h2>Terminé ! 🎉</h2>
                                <div className="qcm-score-circle"><span className="qcm-score-num">{score}</span> / {activeQcm.questions.length}</div>
                                <p>Ton score a été automatiquement enregistré.</p>
                                <button className="btn-danger" onClick={closePopup}>Quitter</button>
                            </div>
                        )}
                    </div>
                </div>
            )}
        </>
    )
}

export default App
