import React, {useState, useEffect} from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  StatusBar,
  ActivityIndicator,
} from 'react-native';
import {useRoute, useNavigation, RouteProp} from '@react-navigation/native';
import {NativeStackNavigationProp} from '@react-navigation/native-stack';
import {MainStackParamList} from '../../navigation/MainNavigator';
import lessonService, {QuizResultData} from '../../services/lessonService';

type QuizResultRouteProp = RouteProp<MainStackParamList, 'QuizResult'>;
type NavigationProp = NativeStackNavigationProp<MainStackParamList>;

const QuizResultScreen: React.FC = () => {
  const route = useRoute<QuizResultRouteProp>();
  const navigation = useNavigation<NavigationProp>();
  const {attemptId, lessonId} = route.params;

  const [loading, setLoading] = useState(true);
  const [result, setResult] = useState<QuizResultData | null>(null);
  const [showDetails, setShowDetails] = useState(false);

  useEffect(() => {
    const fetchResult = async () => {
      try {
        const response = await lessonService.getQuizResult(attemptId);
        if (response.success) {
          setResult(response.data);
        }
      } catch (error) {
        console.error('Error fetching quiz result:', error);
      } finally {
        setLoading(false);
      }
    };
    fetchResult();
  }, [attemptId]);

  if (loading) {
    return (
      <View style={styles.loadingContainer}>
        <ActivityIndicator size="large" color="#4F46E5" />
      </View>
    );
  }

  if (!result) {
    return (
      <View style={styles.errorContainer}>
        <Text style={styles.errorIcon}>😔</Text>
        <Text style={styles.errorText}>Không thể tải kết quả</Text>
        <TouchableOpacity style={styles.retryBtn} onPress={() => navigation.goBack()}>
          <Text style={styles.retryBtnText}>Quay lại</Text>
        </TouchableOpacity>
      </View>
    );
  }

  const passed = result.percentage >= 50;
  const correctCount = result.questions.filter(q => q.is_correct).length;

  const getGradeEmoji = () => {
    if (result.percentage >= 90) return '🏆';
    if (result.percentage >= 70) return '🎉';
    if (result.percentage >= 50) return '👍';
    return '💪';
  };

  return (
    <View style={styles.container}>
      <StatusBar barStyle="light-content" backgroundColor={passed ? '#059669' : '#DC2626'} />

      {/* Score Header */}
      <View style={[styles.scoreHeader, passed ? styles.scoreHeaderPass : styles.scoreHeaderFail]}>
        <Text style={styles.gradeEmoji}>{getGradeEmoji()}</Text>
        <Text style={styles.scoreTitle}>
          {passed ? 'Chúc mừng!' : 'Cần cố gắng thêm!'}
        </Text>

        <View style={styles.scoreCircle}>
          <Text style={styles.scorePercent}>{Math.round(result.percentage)}%</Text>
          <Text style={styles.scoreLabel}>
            {result.score}/{result.total_points} điểm
          </Text>
        </View>

        <View style={styles.scoreStats}>
          <View style={styles.statItem}>
            <Text style={styles.statValue}>{correctCount}</Text>
            <Text style={styles.statLabel}>Đúng</Text>
          </View>
          <View style={styles.statDivider} />
          <View style={styles.statItem}>
            <Text style={styles.statValue}>{result.questions.length - correctCount}</Text>
            <Text style={styles.statLabel}>Sai</Text>
          </View>
          <View style={styles.statDivider} />
          <View style={styles.statItem}>
            <Text style={styles.statValue}>{result.questions.length}</Text>
            <Text style={styles.statLabel}>Tổng</Text>
          </View>
        </View>
      </View>

      <ScrollView style={styles.content} showsVerticalScrollIndicator={false}>
        {/* Toggle Details */}
        {result.show_answers && (
          <TouchableOpacity
            style={styles.toggleBtn}
            onPress={() => setShowDetails(!showDetails)}>
            <Text style={styles.toggleBtnText}>
              {showDetails ? '🔼 Ẩn chi tiết' : '🔽 Xem chi tiết đáp án'}
            </Text>
          </TouchableOpacity>
        )}

        {/* Question Details */}
        {showDetails && result.questions.map((q, index) => (
          <View key={q.question_id} style={[styles.questionCard, q.is_correct ? styles.questionCorrect : styles.questionWrong]}>
            <View style={styles.questionHeader}>
              <View style={[styles.resultBadge, q.is_correct ? styles.badgeCorrect : styles.badgeWrong]}>
                <Text style={styles.resultBadgeText}>
                  {q.is_correct ? '✓' : '✗'}
                </Text>
              </View>
              <Text style={styles.questionNum}>Câu {index + 1}</Text>
              <Text style={styles.questionScore}>
                {q.points_earned}/{q.points} điểm
              </Text>
            </View>

            <Text style={styles.questionText}>{q.content}</Text>

            {/* Options */}
            {q.options.map(option => {
              const isSelected = option.is_selected;
              const isCorrect = option.is_correct;
              let optStyle = styles.optionDefault;
              if (isCorrect) optStyle = styles.optionCorrectBg;
              else if (isSelected && !isCorrect) optStyle = styles.optionWrongBg;

              return (
                <View key={option.id} style={[styles.optionRow, optStyle]}>
                  <Text style={styles.optionIndicator}>
                    {isSelected ? (isCorrect ? '✓' : '✗') : isCorrect ? '✓' : ''}
                  </Text>
                  <Text style={[
                    styles.optionText,
                    isCorrect && styles.optionTextCorrect,
                    isSelected && !isCorrect && styles.optionTextWrong,
                  ]}>
                    {option.option_text}
                  </Text>
                </View>
              );
            })}

            {/* Explanation */}
            {q.explanation && (
              <View style={styles.explanationBox}>
                <Text style={styles.explanationLabel}>💡 Giải thích</Text>
                <Text style={styles.explanationText}>{q.explanation}</Text>
              </View>
            )}
          </View>
        ))}

        <View style={{height: 32}} />
      </ScrollView>

      {/* Bottom */}
      <View style={styles.bottomBar}>
        <TouchableOpacity
          style={styles.backToLessonBtn}
          onPress={() => {
            // Go back to LessonDetail (pop QuizResult + QuizScreen)
            navigation.pop(1);
          }}>
          <Text style={styles.backToLessonText}>← Quay lại bài học</Text>
        </TouchableOpacity>
      </View>
    </View>
  );
};

const styles = StyleSheet.create({
  container: {flex: 1, backgroundColor: '#F3F4F6'},
  loadingContainer: {flex: 1, justifyContent: 'center', alignItems: 'center', backgroundColor: '#F3F4F6'},
  errorContainer: {flex: 1, justifyContent: 'center', alignItems: 'center', backgroundColor: '#F3F4F6', padding: 40},
  errorIcon: {fontSize: 48, marginBottom: 16},
  errorText: {fontSize: 16, color: '#6B7280', marginBottom: 20},
  retryBtn: {backgroundColor: '#4F46E5', paddingHorizontal: 24, paddingVertical: 12, borderRadius: 10},
  retryBtnText: {color: '#FFF', fontWeight: '600'},

  // Score Header
  scoreHeader: {
    alignItems: 'center', paddingTop: 24, paddingBottom: 28, paddingHorizontal: 20,
  },
  scoreHeaderPass: {backgroundColor: '#059669'},
  scoreHeaderFail: {backgroundColor: '#DC2626'},
  gradeEmoji: {fontSize: 48, marginBottom: 8},
  scoreTitle: {fontSize: 20, fontWeight: '700', color: '#FFF', marginBottom: 20},
  scoreCircle: {
    width: 120, height: 120, borderRadius: 60,
    backgroundColor: 'rgba(255,255,255,0.2)',
    justifyContent: 'center', alignItems: 'center', marginBottom: 20,
  },
  scorePercent: {fontSize: 36, fontWeight: '800', color: '#FFF'},
  scoreLabel: {fontSize: 13, color: 'rgba(255,255,255,0.8)', marginTop: 2},
  scoreStats: {flexDirection: 'row', alignItems: 'center', gap: 0},
  statItem: {alignItems: 'center', paddingHorizontal: 24},
  statValue: {fontSize: 24, fontWeight: '700', color: '#FFF'},
  statLabel: {fontSize: 12, color: 'rgba(255,255,255,0.7)', marginTop: 2},
  statDivider: {width: 1, height: 36, backgroundColor: 'rgba(255,255,255,0.3)'},

  content: {flex: 1},

  // Toggle
  toggleBtn: {
    backgroundColor: '#FFF', marginHorizontal: 16, marginTop: 16,
    padding: 14, borderRadius: 12, alignItems: 'center',
    shadowColor: '#000', shadowOffset: {width: 0, height: 1}, shadowOpacity: 0.04, shadowRadius: 4, elevation: 1,
  },
  toggleBtnText: {fontSize: 14, fontWeight: '600', color: '#4F46E5'},

  // Question Cards
  questionCard: {
    backgroundColor: '#FFF', marginHorizontal: 16, marginTop: 12,
    borderRadius: 14, padding: 16, borderLeftWidth: 4,
    shadowColor: '#000', shadowOffset: {width: 0, height: 1}, shadowOpacity: 0.04, shadowRadius: 4, elevation: 1,
  },
  questionCorrect: {borderLeftColor: '#10B981'},
  questionWrong: {borderLeftColor: '#EF4444'},
  questionHeader: {flexDirection: 'row', alignItems: 'center', marginBottom: 10, gap: 8},
  resultBadge: {width: 26, height: 26, borderRadius: 13, justifyContent: 'center', alignItems: 'center'},
  badgeCorrect: {backgroundColor: '#D1FAE5'},
  badgeWrong: {backgroundColor: '#FEE2E2'},
  resultBadgeText: {fontSize: 14, fontWeight: '700'},
  questionNum: {fontSize: 14, fontWeight: '600', color: '#1F2937', flex: 1},
  questionScore: {fontSize: 13, fontWeight: '600', color: '#6B7280'},
  questionText: {fontSize: 15, color: '#1F2937', lineHeight: 24, marginBottom: 12},

  // Options
  optionRow: {
    flexDirection: 'row', alignItems: 'center',
    padding: 10, borderRadius: 8, marginBottom: 4, gap: 8,
  },
  optionDefault: {backgroundColor: '#F9FAFB'},
  optionCorrectBg: {backgroundColor: '#D1FAE5'},
  optionWrongBg: {backgroundColor: '#FEE2E2'},
  optionIndicator: {width: 20, fontSize: 14, fontWeight: '700', textAlign: 'center'},
  optionText: {flex: 1, fontSize: 14, color: '#374151'},
  optionTextCorrect: {color: '#065F46', fontWeight: '500'},
  optionTextWrong: {color: '#991B1B', fontWeight: '500'},

  // Explanation
  explanationBox: {
    marginTop: 8, padding: 12, backgroundColor: '#FEF3C7', borderRadius: 10,
  },
  explanationLabel: {fontSize: 13, fontWeight: '700', color: '#92400E', marginBottom: 4},
  explanationText: {fontSize: 13, color: '#78350F', lineHeight: 20},

  // Bottom
  bottomBar: {
    backgroundColor: '#FFF', padding: 16, borderTopWidth: 1, borderTopColor: '#E5E7EB',
  },
  backToLessonBtn: {
    backgroundColor: '#4F46E5', borderRadius: 12, paddingVertical: 14, alignItems: 'center',
  },
  backToLessonText: {color: '#FFF', fontSize: 15, fontWeight: '600'},
});

export default QuizResultScreen;
