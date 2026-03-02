import React, {useState, useEffect, useRef, useCallback} from 'react';
import {
  View,
  Text,
  StyleSheet,
  TouchableOpacity,
  ScrollView,
  StatusBar,
  ActivityIndicator,
  Alert,
} from 'react-native';
import {useRoute, useNavigation, RouteProp} from '@react-navigation/native';
import {NativeStackNavigationProp} from '@react-navigation/native-stack';
import {MainStackParamList} from '../../navigation/MainNavigator';
import lessonService, {QuizQuestion, QuizStartData} from '../../services/lessonService';

type QuizScreenRouteProp = RouteProp<MainStackParamList, 'QuizScreen'>;
type NavigationProp = NativeStackNavigationProp<MainStackParamList>;

const QuizScreen: React.FC = () => {
  const route = useRoute<QuizScreenRouteProp>();
  const navigation = useNavigation<NavigationProp>();
  const {quizId, lessonId} = route.params;

  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [quizData, setQuizData] = useState<QuizStartData | null>(null);
  const [currentIndex, setCurrentIndex] = useState(0);
  const [answers, setAnswers] = useState<Record<number, number>>({}); // questionId -> optionId
  const [timeLeft, setTimeLeft] = useState<number | null>(null);
  const timerRef = useRef<ReturnType<typeof setInterval> | null>(null);

  const startQuiz = useCallback(async () => {
    try {
      const response = await lessonService.startQuiz(quizId);
      if (response.success) {
        setQuizData(response.data);
        if (response.data.time_limit) {
          setTimeLeft(response.data.time_limit * 60); // convert minutes to seconds
        }
      }
    } catch (error: any) {
      const msg = error.response?.data?.message || 'Không thể bắt đầu quiz';
      Alert.alert('Lỗi', msg, [{text: 'Quay lại', onPress: () => navigation.goBack()}]);
    } finally {
      setLoading(false);
    }
  }, [quizId, navigation]);

  useEffect(() => {
    startQuiz();
  }, [startQuiz]);

  // Timer
  useEffect(() => {
    if (timeLeft === null || timeLeft <= 0) return;

    timerRef.current = setInterval(() => {
      setTimeLeft(prev => {
        if (prev === null || prev <= 1) {
          clearInterval(timerRef.current!);
          handleSubmit(true);
          return 0;
        }
        return prev - 1;
      });
    }, 1000);

    return () => {
      if (timerRef.current) clearInterval(timerRef.current);
    };
  }, [timeLeft !== null]); // eslint-disable-line

  const formatTime = (seconds: number): string => {
    const m = Math.floor(seconds / 60);
    const s = seconds % 60;
    return `${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
  };

  const selectAnswer = (questionId: number, optionId: number) => {
    setAnswers(prev => ({...prev, [questionId]: optionId}));
  };

  const handleSubmit = async (isTimeout = false) => {
    if (!quizData) return;

    if (!isTimeout) {
      const unanswered = quizData.questions.length - Object.keys(answers).length;
      if (unanswered > 0) {
        Alert.alert(
          'Xác nhận nộp bài',
          `Bạn còn ${unanswered} câu chưa trả lời. Bạn có chắc muốn nộp?`,
          [
            {text: 'Tiếp tục làm', style: 'cancel'},
            {text: 'Nộp bài', style: 'destructive', onPress: () => doSubmit()},
          ],
        );
        return;
      }
      Alert.alert('Xác nhận nộp bài', 'Bạn đã trả lời hết. Nộp bài ngay?', [
        {text: 'Kiểm tra lại', style: 'cancel'},
        {text: 'Nộp bài', onPress: () => doSubmit()},
      ]);
      return;
    }

    doSubmit();
  };

  const doSubmit = async () => {
    if (!quizData) return;
    setSubmitting(true);

    if (timerRef.current) clearInterval(timerRef.current);

    try {
      const answerArray = quizData.questions.map(q => ({
        question_id: q.id,
        option_id: answers[q.id] || 0,
      }));

      const response = await lessonService.submitQuiz(quizId, quizData.attempt_id, answerArray);
      if (response.success) {
        navigation.replace('QuizResult', {
          attemptId: response.data.attempt_id,
          lessonId,
        });
      }
    } catch (error: any) {
      Alert.alert('Lỗi', error.response?.data?.message || 'Không thể nộp bài');
    } finally {
      setSubmitting(false);
    }
  };

  if (loading) {
    return (
      <View style={styles.loadingContainer}>
        <ActivityIndicator size="large" color="#4F46E5" />
        <Text style={styles.loadingText}>Đang tải câu hỏi...</Text>
      </View>
    );
  }

  if (!quizData) return null;

  const question = quizData.questions[currentIndex];
  const answeredCount = Object.keys(answers).length;
  const totalQuestions = quizData.questions.length;

  return (
    <View style={styles.container}>
      <StatusBar barStyle="dark-content" backgroundColor="#FFFFFF" />

      {/* Header */}
      <View style={styles.header}>
        <TouchableOpacity
          onPress={() => {
            Alert.alert('Thoát bài làm?', 'Bài làm sẽ không được lưu.', [
              {text: 'Tiếp tục', style: 'cancel'},
              {text: 'Thoát', style: 'destructive', onPress: () => navigation.goBack()},
            ]);
          }}>
          <Text style={styles.headerClose}>✕</Text>
        </TouchableOpacity>

        <View style={styles.headerCenter}>
          <Text style={styles.headerTitle} numberOfLines={1}>{quizData.quiz_title}</Text>
          <Text style={styles.headerSub}>
            Câu {currentIndex + 1}/{totalQuestions} • Đã trả lời {answeredCount}/{totalQuestions}
          </Text>
        </View>

        {timeLeft !== null && (
          <View style={[styles.timerBadge, timeLeft < 60 && styles.timerBadgeUrgent]}>
            <Text style={[styles.timerText, timeLeft < 60 && styles.timerTextUrgent]}>
              ⏱ {formatTime(timeLeft)}
            </Text>
          </View>
        )}
      </View>

      {/* Progress */}
      <View style={styles.progressBar}>
        <View style={[styles.progressFill, {width: `${((currentIndex + 1) / totalQuestions) * 100}%`}]} />
      </View>

      {/* Question */}
      <ScrollView style={styles.content} contentContainerStyle={styles.contentInner} showsVerticalScrollIndicator={false}>
        <View style={styles.questionHeader}>
          <View style={styles.questionBadge}>
            <Text style={styles.questionBadgeText}>Câu {currentIndex + 1}</Text>
          </View>
          <Text style={styles.questionPoints}>{question.points} điểm</Text>
        </View>

        <Text style={styles.questionText}>{question.content}</Text>

        {/* Options */}
        <View style={styles.optionsContainer}>
          {question.options.map((option, index) => {
            const isSelected = answers[question.id] === option.id;
            const optionLetter = String.fromCharCode(65 + index);
            return (
              <TouchableOpacity
                key={option.id}
                style={[styles.optionCard, isSelected && styles.optionCardSelected]}
                onPress={() => selectAnswer(question.id, option.id)}
                activeOpacity={0.7}>
                <View style={[styles.optionLetter, isSelected && styles.optionLetterSelected]}>
                  <Text style={[styles.optionLetterText, isSelected && styles.optionLetterTextSelected]}>
                    {optionLetter}
                  </Text>
                </View>
                <Text style={[styles.optionText, isSelected && styles.optionTextSelected]}>
                  {option.option_text}
                </Text>
              </TouchableOpacity>
            );
          })}
        </View>
      </ScrollView>

      {/* Bottom Navigation */}
      <View style={styles.bottomBar}>
        <TouchableOpacity
          style={[styles.bottomBtn, currentIndex === 0 && styles.bottomBtnDisabled]}
          onPress={() => setCurrentIndex(i => Math.max(0, i - 1))}
          disabled={currentIndex === 0}>
          <Text style={[styles.bottomBtnText, currentIndex === 0 && styles.bottomBtnTextDisabled]}>
            ← Trước
          </Text>
        </TouchableOpacity>

        {/* Question dots */}
        <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.questionDots}>
          {quizData.questions.map((q, i) => (
            <TouchableOpacity key={q.id} onPress={() => setCurrentIndex(i)}>
              <View style={[
                styles.qDot,
                i === currentIndex && styles.qDotActive,
                answers[q.id] !== undefined && styles.qDotAnswered,
              ]}>
                <Text style={[
                  styles.qDotText,
                  i === currentIndex && styles.qDotTextActive,
                  answers[q.id] !== undefined && styles.qDotTextAnswered,
                ]}>{i + 1}</Text>
              </View>
            </TouchableOpacity>
          ))}
        </ScrollView>

        {currentIndex < totalQuestions - 1 ? (
          <TouchableOpacity
            style={styles.bottomBtn}
            onPress={() => setCurrentIndex(i => Math.min(totalQuestions - 1, i + 1))}>
            <Text style={styles.bottomBtnText}>Tiếp →</Text>
          </TouchableOpacity>
        ) : (
          <TouchableOpacity
            style={[styles.bottomBtn, styles.submitBtn]}
            onPress={() => handleSubmit(false)}
            disabled={submitting}>
            {submitting ? (
              <ActivityIndicator size="small" color="#FFF" />
            ) : (
              <Text style={styles.submitBtnText}>Nộp bài</Text>
            )}
          </TouchableOpacity>
        )}
      </View>
    </View>
  );
};

const styles = StyleSheet.create({
  container: {flex: 1, backgroundColor: '#F9FAFB'},
  loadingContainer: {flex: 1, justifyContent: 'center', alignItems: 'center', backgroundColor: '#F9FAFB', gap: 12},
  loadingText: {fontSize: 15, color: '#6B7280'},

  // Header
  header: {
    flexDirection: 'row', alignItems: 'center',
    backgroundColor: '#FFF', paddingHorizontal: 16, paddingVertical: 12,
    borderBottomWidth: 1, borderBottomColor: '#E5E7EB', gap: 12,
  },
  headerClose: {fontSize: 20, color: '#6B7280', fontWeight: '600'},
  headerCenter: {flex: 1},
  headerTitle: {fontSize: 16, fontWeight: '700', color: '#1F2937'},
  headerSub: {fontSize: 12, color: '#9CA3AF', marginTop: 2},
  timerBadge: {
    backgroundColor: '#EEF2FF', paddingHorizontal: 10, paddingVertical: 6, borderRadius: 8,
  },
  timerBadgeUrgent: {backgroundColor: '#FEE2E2'},
  timerText: {fontSize: 14, fontWeight: '700', color: '#4F46E5'},
  timerTextUrgent: {color: '#DC2626'},

  // Progress
  progressBar: {height: 3, backgroundColor: '#E5E7EB'},
  progressFill: {height: '100%', backgroundColor: '#4F46E5'},

  // Content
  content: {flex: 1},
  contentInner: {padding: 20},

  questionHeader: {flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 16},
  questionBadge: {
    backgroundColor: '#EEF2FF', paddingHorizontal: 12, paddingVertical: 6, borderRadius: 8,
  },
  questionBadgeText: {fontSize: 13, fontWeight: '700', color: '#4F46E5'},
  questionPoints: {fontSize: 13, fontWeight: '600', color: '#9CA3AF'},

  questionText: {fontSize: 18, fontWeight: '600', color: '#1F2937', lineHeight: 28, marginBottom: 24},

  optionsContainer: {gap: 10},
  optionCard: {
    flexDirection: 'row', alignItems: 'center',
    backgroundColor: '#FFF', borderRadius: 14, padding: 16,
    borderWidth: 2, borderColor: '#E5E7EB',
  },
  optionCardSelected: {borderColor: '#4F46E5', backgroundColor: '#EEF2FF'},
  optionLetter: {
    width: 36, height: 36, borderRadius: 18,
    backgroundColor: '#F3F4F6', justifyContent: 'center', alignItems: 'center', marginRight: 14,
  },
  optionLetterSelected: {backgroundColor: '#4F46E5'},
  optionLetterText: {fontSize: 15, fontWeight: '700', color: '#6B7280'},
  optionLetterTextSelected: {color: '#FFF'},
  optionText: {flex: 1, fontSize: 15, color: '#374151', lineHeight: 22},
  optionTextSelected: {color: '#1F2937', fontWeight: '500'},

  // Bottom
  bottomBar: {
    flexDirection: 'row', alignItems: 'center',
    backgroundColor: '#FFF', paddingHorizontal: 12, paddingVertical: 10,
    borderTopWidth: 1, borderTopColor: '#E5E7EB', gap: 8,
  },
  bottomBtn: {
    paddingHorizontal: 16, paddingVertical: 10, borderRadius: 10,
    backgroundColor: '#F3F4F6',
  },
  bottomBtnDisabled: {opacity: 0.4},
  bottomBtnText: {fontSize: 14, fontWeight: '600', color: '#374151'},
  bottomBtnTextDisabled: {color: '#9CA3AF'},
  submitBtn: {backgroundColor: '#4F46E5'},
  submitBtnText: {fontSize: 14, fontWeight: '700', color: '#FFF'},

  questionDots: {flexDirection: 'row', alignItems: 'center', gap: 6, paddingHorizontal: 4},
  qDot: {
    width: 28, height: 28, borderRadius: 14,
    backgroundColor: '#F3F4F6', justifyContent: 'center', alignItems: 'center',
  },
  qDotActive: {backgroundColor: '#EEF2FF', borderWidth: 2, borderColor: '#4F46E5'},
  qDotAnswered: {backgroundColor: '#D1FAE5'},
  qDotText: {fontSize: 12, fontWeight: '600', color: '#9CA3AF'},
  qDotTextActive: {color: '#4F46E5'},
  qDotTextAnswered: {color: '#065F46'},
});

export default QuizScreen;
